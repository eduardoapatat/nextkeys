<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per attempt to pay an order (docs/domain/payments.md, "Design").
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('number');
            $table->string('gateway', 20);
            $table->string('buy_order', 26)->unique();
            $table->char('token', 64)->nullable()->unique();
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('initialized');
            $table->smallInteger('response_code')->nullable();
            $table->string('authorization_code', 20)->nullable();
            $table->string('payment_type_code', 2)->nullable();
            $table->unsignedSmallInteger('installments_number')->nullable();
            $table->unsignedBigInteger('installments_amount')->nullable();
            $table->char('card_last_digits', 4)->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->char('accounting_date', 4)->nullable();
            $table->string('refund_type', 10)->nullable();
            $table->unsignedBigInteger('refunded_amount')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'number']);
            $table->index(['status', 'created_at']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE payment_attempts
                ADD CONSTRAINT payment_attempts_number_range
                    CHECK (number BETWEEN 1 AND 5),
                ADD CONSTRAINT payment_attempts_gateway_check
                    CHECK (gateway IN ('webpay_plus')),
                ADD CONSTRAINT payment_attempts_status_check
                    CHECK (status IN ('initialized', 'authorized', 'rejected', 'cancelled', 'aborted', 'refunded')),
                ADD CONSTRAINT payment_attempts_amount_positive
                    CHECK (amount > 0),
                ADD CONSTRAINT payment_attempts_token_format
                    CHECK (token ~ '^[0-9a-f]{64}$'),
                ADD CONSTRAINT payment_attempts_token_matches_status
                    CHECK (token IS NOT NULL OR status IN ('initialized', 'aborted')),
                ADD CONSTRAINT payment_attempts_finished_at_matches_status
                    CHECK ((finished_at IS NULL) = (status = 'initialized')),
                ADD CONSTRAINT payment_attempts_receipt_matches_status
                    CHECK (status <> 'authorized'
                        OR (response_code IS NOT DISTINCT FROM 0 AND authorization_code IS NOT NULL AND transaction_date IS NOT NULL)),
                ADD CONSTRAINT payment_attempts_refund_type_check
                    CHECK (refund_type IN ('reversed', 'nullified')),
                ADD CONSTRAINT payment_attempts_refund_matches_status
                    CHECK ((refunded_at IS NOT NULL) = (status = 'refunded')
                        AND (refund_type IS NOT NULL) = (status = 'refunded'))
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION payment_attempts_check_status_transition() RETURNS trigger AS $$
            BEGIN
                IF NEW.status = OLD.status THEN
                    RETURN NEW;
                END IF;

                IF OLD.status = 'initialized' THEN
                    RETURN NEW;
                END IF;

                RAISE EXCEPTION 'payment_attempts_status_transition: a payment attempt cannot go from % to %', OLD.status, NEW.status
                    USING ERRCODE = 'check_violation';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER payment_attempts_status_transition
                BEFORE UPDATE OF status ON payment_attempts
                FOR EACH ROW EXECUTE FUNCTION payment_attempts_check_status_transition();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
        DB::unprepared('DROP FUNCTION IF EXISTS payment_attempts_check_status_transition()');
    }
};
