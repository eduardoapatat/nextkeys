<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A late payment no longer goes `expired` → `paid`: the order is reopened
 * (`expired` → `pending`, stock reserved again) before the payment is
 * committed, so stock is held exactly while an order is pending or paid
 * (docs/domain/orders.md, "Reservation").
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->replaceTransitions("('expired', 'pending')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->replaceTransitions("('expired', 'paid')");
    }

    private function replaceTransitions(string $fromExpired): void
    {
        DB::statement(<<<SQL
            CREATE OR REPLACE FUNCTION orders_check_status_transition() RETURNS trigger AS \$\$
            BEGIN
                IF NEW.status = OLD.status THEN
                    RETURN NEW;
                END IF;

                IF (OLD.status, NEW.status) IN (
                    ('pending', 'paid'),
                    ('pending', 'cancelled'),
                    ('pending', 'expired'),
                    {$fromExpired},
                    ('paid', 'completed')
                ) THEN
                    RETURN NEW;
                END IF;

                RAISE EXCEPTION 'orders_status_transition: an order cannot go from % to %', OLD.status, NEW.status
                    USING ERRCODE = 'check_violation';
            END;
            \$\$ LANGUAGE plpgsql;
            SQL);
    }
};
