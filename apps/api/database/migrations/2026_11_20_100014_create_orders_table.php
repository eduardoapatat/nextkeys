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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('reference', 9)->unique();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('total');
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE orders
                ADD CONSTRAINT orders_status_check
                    CHECK (status IN ('pending', 'paid', 'completed', 'cancelled', 'expired')),
                ADD CONSTRAINT orders_total_positive
                    CHECK (total > 0),
                ADD CONSTRAINT orders_reference_format
                    CHECK (reference ~ '^NK-[0-9A-HJKMNP-TV-Z]{6}$'),
                ADD CONSTRAINT orders_paid_at_matches_status
                    CHECK ((paid_at IS NOT NULL) = (status IN ('paid', 'completed'))),
                ADD CONSTRAINT orders_completed_at_matches_status
                    CHECK ((completed_at IS NOT NULL) = (status = 'completed')),
                ADD CONSTRAINT orders_cancelled_at_matches_status
                    CHECK ((cancelled_at IS NOT NULL) = (status = 'cancelled'))
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION orders_check_status_transition() RETURNS trigger AS $$
            BEGIN
                IF NEW.status = OLD.status THEN
                    RETURN NEW;
                END IF;

                IF (OLD.status, NEW.status) IN (
                    ('pending', 'paid'),
                    ('pending', 'cancelled'),
                    ('pending', 'expired'),
                    ('expired', 'paid'),
                    ('paid', 'completed')
                ) THEN
                    RETURN NEW;
                END IF;

                RAISE EXCEPTION 'orders_status_transition: an order cannot go from % to %', OLD.status, NEW.status
                    USING ERRCODE = 'check_violation';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER orders_status_transition
                BEFORE UPDATE OF status ON orders
                FOR EACH ROW EXECUTE FUNCTION orders_check_status_transition();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        DB::unprepared('DROP FUNCTION IF EXISTS orders_check_status_transition()');
    }
};
