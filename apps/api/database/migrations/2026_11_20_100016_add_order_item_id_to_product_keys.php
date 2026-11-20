<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_keys', function (Blueprint $table) {
            $table->foreignId('order_item_id')->nullable()->after('status');

            $table->index('order_item_id');

            // The line must be of the key's own product.
            $table->foreign(['order_item_id', 'product_id'])
                ->references(['id', 'product_id'])
                ->on('order_items')
                ->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE product_keys
                ADD CONSTRAINT product_keys_order_item_matches_status
                    CHECK (
                        (status <> 'available' OR order_item_id IS NULL)
                        AND (status NOT IN ('reserved', 'sold') OR order_item_id IS NOT NULL)
                    )
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION product_keys_check_status_transition() RETURNS trigger AS $$
            BEGIN
                IF OLD.status = 'sold'
                    AND (NEW.status, NEW.order_item_id) IS DISTINCT FROM (OLD.status, OLD.order_item_id) THEN
                    RAISE EXCEPTION 'product_keys_status_transition: a sold key cannot change'
                        USING ERRCODE = 'check_violation';
                END IF;

                IF NEW.status <> OLD.status AND (OLD.status, NEW.status) NOT IN (
                    ('available', 'reserved'),
                    ('reserved', 'available'),
                    ('reserved', 'sold')
                ) THEN
                    RAISE EXCEPTION 'product_keys_status_transition: a key cannot go from % to %', OLD.status, NEW.status
                        USING ERRCODE = 'check_violation';
                END IF;

                IF OLD.order_item_id IS NOT NULL AND NEW.order_item_id IS NOT NULL
                    AND NEW.order_item_id <> OLD.order_item_id THEN
                    RAISE EXCEPTION 'product_keys_status_transition: a key cannot move between orders without being released'
                        USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER product_keys_status_transition
                BEFORE UPDATE OF status, order_item_id ON product_keys
                FOR EACH ROW EXECUTE FUNCTION product_keys_check_status_transition();

            -- Runs after the statement's rows are written, so a bulk reservation
            -- counts all its keys. Locking the line serializes concurrent writers.
            CREATE OR REPLACE FUNCTION product_keys_check_order_item_quantity() RETURNS trigger AS $$
            DECLARE
                line_quantity integer;
                linked integer;
            BEGIN
                SELECT quantity INTO line_quantity FROM order_items WHERE id = NEW.order_item_id FOR UPDATE;
                SELECT count(*) INTO linked FROM product_keys WHERE order_item_id = NEW.order_item_id;

                IF linked > line_quantity THEN
                    RAISE EXCEPTION 'product_keys_order_item_quantity: order item % has % keys for a quantity of %',
                        NEW.order_item_id, linked, line_quantity
                        USING ERRCODE = 'check_violation';
                END IF;

                RETURN NULL;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER product_keys_order_item_quantity
                AFTER INSERT OR UPDATE OF order_item_id ON product_keys
                FOR EACH ROW WHEN (NEW.order_item_id IS NOT NULL)
                EXECUTE FUNCTION product_keys_check_order_item_quantity();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS product_keys_order_item_quantity ON product_keys;
            DROP FUNCTION IF EXISTS product_keys_check_order_item_quantity();
            DROP TRIGGER IF EXISTS product_keys_status_transition ON product_keys;
            DROP FUNCTION IF EXISTS product_keys_check_status_transition();
            ALTER TABLE product_keys DROP CONSTRAINT IF EXISTS product_keys_order_item_matches_status;
            SQL);

        Schema::table('product_keys', function (Blueprint $table) {
            $table->dropForeign(['order_item_id', 'product_id']);
            $table->dropIndex(['order_item_id']);
            $table->dropColumn('order_item_id');
        });
    }
};
