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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->index()->constrained()->restrictOnDelete();
            $table->string('type');
            $table->string('slug')->unique();
            $table->foreignId('activation_service_id')->nullable()->index()->constrained()->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->index()->constrained()->restrictOnDelete();
            $table->integer('price');
            $table->integer('stock')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            // Redundant with the primary key, but required as the target of
            // the composite foreign key from product_keys (product_id, product_type).
            $table->unique(['id', 'type']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE products
                ADD CONSTRAINT products_type_check
                    CHECK (type IN ('digital_key', 'license', 'physical')),
                ADD CONSTRAINT products_price_positive
                    CHECK (price > 0),
                ADD CONSTRAINT products_stock_non_negative
                    CHECK (stock >= 0),
                ADD CONSTRAINT products_slug_format
                    CHECK (slug ~ '^[a-z0-9]+(-[a-z0-9]+)*$'),
                ADD CONSTRAINT products_key_types_require_service_and_region
                    CHECK (type = 'physical' OR (activation_service_id IS NOT NULL AND region_id IS NOT NULL)),
                ADD CONSTRAINT products_stock_only_for_physical
                    CHECK ((type = 'physical') = (stock IS NOT NULL))
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION products_prevent_type_change() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                IF NEW.type IS DISTINCT FROM OLD.type THEN
                    RAISE EXCEPTION 'products.type is immutable (product %: % -> %)', OLD.id, OLD.type, NEW.type
                        USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;

            CREATE TRIGGER products_type_immutable
                BEFORE UPDATE OF type ON products
                FOR EACH ROW EXECUTE FUNCTION products_prevent_type_change();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
        DB::unprepared('DROP FUNCTION IF EXISTS products_prevent_type_change()');
    }
};
