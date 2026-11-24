<?php

use App\Enums\Carrier;
use App\Enums\ChileRegion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shipping for orders with physical lines (docs/domain/shipping.md): the flat
 * fee on the order, one shipment per order with a copy of the address, its
 * own status (`pending` → `shipped` → `delivered`) and the rules that tie it
 * to the order.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedInteger('shipping_cost')->default(0);
        });

        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_shipping_cost_not_negative CHECK (shipping_cost >= 0)');

        Schema::create('shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('phone');
            $table->string('region');
            $table->string('commune');
            $table->string('street');
            $table->string('street_number');
            $table->string('apartment')->nullable();
            $table->string('status');
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        $regions = implode(', ', array_map(fn (ChileRegion $region): string => "'{$region->value}'", ChileRegion::cases()));
        $carriers = implode(', ', array_map(fn (Carrier $carrier): string => "'{$carrier->value}'", Carrier::cases()));

        DB::statement("ALTER TABLE shipments ADD CONSTRAINT shipments_status_check CHECK (status IN ('pending', 'shipped', 'delivered'))");
        DB::statement("ALTER TABLE shipments ADD CONSTRAINT shipments_region_check CHECK (region IN ({$regions}))");
        DB::statement("ALTER TABLE shipments ADD CONSTRAINT shipments_carrier_check CHECK (carrier IN ({$carriers}))");
        DB::statement("ALTER TABLE shipments ADD CONSTRAINT shipments_shipped_matches_status CHECK ((carrier IS NOT NULL AND tracking_number IS NOT NULL AND shipped_at IS NOT NULL) = (status IN ('shipped', 'delivered')) AND (carrier IS NULL) = (tracking_number IS NULL) AND (carrier IS NULL) = (shipped_at IS NULL))");
        DB::statement("ALTER TABLE shipments ADD CONSTRAINT shipments_delivered_at_matches_status CHECK ((delivered_at IS NOT NULL) = (status = 'delivered'))");
        DB::statement("ALTER TABLE shipments ADD CONSTRAINT shipments_tracking_number_not_blank CHECK (tracking_number IS NULL OR btrim(tracking_number) <> '')");

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION shipments_check_status_transition() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                IF NEW.status = OLD.status THEN
                    RETURN NEW;
                END IF;

                IF OLD.status = 'pending' AND NEW.status = 'shipped' THEN
                    IF NOT EXISTS (SELECT 1 FROM orders WHERE id = NEW.order_id AND status = 'paid') THEN
                        RAISE EXCEPTION 'shipments_status_transition: only a paid order ships'
                            USING ERRCODE = 'check_violation';
                    END IF;

                    RETURN NEW;
                END IF;

                IF OLD.status = 'shipped' AND NEW.status = 'delivered' THEN
                    RETURN NEW;
                END IF;

                RAISE EXCEPTION 'shipments_status_transition: a shipment cannot go from % to %', OLD.status, NEW.status
                    USING ERRCODE = 'check_violation';
            END;
            $$;

            CREATE TRIGGER shipments_status_transition
                BEFORE UPDATE OF status ON shipments
                FOR EACH ROW EXECUTE FUNCTION shipments_check_status_transition();

            CREATE OR REPLACE FUNCTION orders_assert_shipment(checked_order_id bigint) RETURNS void
            LANGUAGE plpgsql AS $$
            DECLARE
                order_shipping_cost integer;
                has_physical_items boolean;
                has_shipment boolean;
            BEGIN
                SELECT shipping_cost INTO order_shipping_cost FROM orders WHERE id = checked_order_id;

                IF NOT FOUND THEN
                    RETURN;
                END IF;

                has_physical_items := EXISTS (SELECT 1 FROM order_items WHERE order_id = checked_order_id AND product_type = 'physical');
                has_shipment := EXISTS (SELECT 1 FROM shipments WHERE order_id = checked_order_id);

                IF has_physical_items AND NOT has_shipment THEN
                    RAISE EXCEPTION 'orders_shipment_required: order % has physical lines and no shipment', checked_order_id
                        USING ERRCODE = 'check_violation';
                END IF;

                IF has_shipment AND NOT has_physical_items THEN
                    RAISE EXCEPTION 'orders_shipment_required: order % ships but has no physical lines', checked_order_id
                        USING ERRCODE = 'check_violation';
                END IF;

                IF NOT has_shipment AND order_shipping_cost <> 0 THEN
                    RAISE EXCEPTION 'orders_shipment_required: order % charges shipping but does not ship', checked_order_id
                        USING ERRCODE = 'check_violation';
                END IF;
            END;
            $$;

            CREATE OR REPLACE FUNCTION orders_check_shipment() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_TABLE_NAME = 'orders' THEN
                    PERFORM orders_assert_shipment(NEW.id);

                    RETURN NULL;
                END IF;

                IF TG_OP IN ('INSERT', 'UPDATE') THEN
                    PERFORM orders_assert_shipment(NEW.order_id);
                END IF;

                IF TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND OLD.order_id <> NEW.order_id) THEN
                    PERFORM orders_assert_shipment(OLD.order_id);
                END IF;

                RETURN NULL;
            END;
            $$;

            CREATE CONSTRAINT TRIGGER orders_shipment_required
                AFTER INSERT OR UPDATE OF shipping_cost ON orders
                DEFERRABLE INITIALLY DEFERRED
                FOR EACH ROW EXECUTE FUNCTION orders_check_shipment();

            CREATE CONSTRAINT TRIGGER order_items_shipment_required
                AFTER INSERT OR UPDATE OF order_id, product_type OR DELETE ON order_items
                DEFERRABLE INITIALLY DEFERRED
                FOR EACH ROW EXECUTE FUNCTION orders_check_shipment();

            CREATE CONSTRAINT TRIGGER shipments_shipment_required
                AFTER INSERT OR UPDATE OF order_id OR DELETE ON shipments
                DEFERRABLE INITIALLY DEFERRED
                FOR EACH ROW EXECUTE FUNCTION orders_check_shipment();
            SQL);

        $this->replaceOrderTransitions(<<<'SQL'
                IF OLD.status = 'paid' AND NEW.status = 'completed'
                    AND EXISTS (SELECT 1 FROM shipments WHERE order_id = NEW.id AND status <> 'delivered') THEN
                    RAISE EXCEPTION 'orders_status_transition: an order completes only once its shipment is delivered'
                        USING ERRCODE = 'check_violation';
                END IF;

            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->replaceOrderTransitions('');

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS orders_shipment_required ON orders;
            DROP TRIGGER IF EXISTS order_items_shipment_required ON order_items;
            DROP TABLE IF EXISTS shipments;
            DROP FUNCTION IF EXISTS orders_check_shipment();
            DROP FUNCTION IF EXISTS orders_assert_shipment(bigint);
            DROP FUNCTION IF EXISTS shipments_check_status_transition();
            SQL);

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('shipping_cost');
        });
    }

    /**
     * The order transitions of `2026_11_20_100017`, with an extra guard run
     * before them.
     */
    private function replaceOrderTransitions(string $guard): void
    {
        DB::statement(<<<SQL
            CREATE OR REPLACE FUNCTION orders_check_status_transition() RETURNS trigger AS \$\$
            BEGIN
                IF NEW.status = OLD.status THEN
                    RETURN NEW;
                END IF;

            {$guard}
                IF (OLD.status, NEW.status) IN (
                    ('pending', 'paid'),
                    ('pending', 'cancelled'),
                    ('pending', 'expired'),
                    ('expired', 'pending'),
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
