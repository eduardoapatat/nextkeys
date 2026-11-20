<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A product's identity is title + type + activation service + region + the
 * exact set of platforms (docs/domain/catalog.md). The set lives in the pivot,
 * so no unique index can express it: a deferred constraint trigger checks it
 * at commit, once the product and all its platforms are written.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION products_assert_identity(target_product_id bigint) RETURNS void
            LANGUAGE plpgsql AS $$
            DECLARE
                product products%ROWTYPE;
                platform_count integer;
                platform_set bigint[];
            BEGIN
                SELECT * INTO product FROM products WHERE id = target_product_id;

                -- Deleted product: its pivot rows were cascaded, nothing to check.
                IF NOT FOUND THEN
                    RETURN;
                END IF;

                -- Serialize concurrent writers of the same title's products.
                -- Namespace 1 = products (see "Advisory locks" in catalog.md).
                PERFORM pg_advisory_xact_lock(1, product.title_id::integer);

                SELECT count(*), array_agg(platform_id ORDER BY platform_id)
                    INTO platform_count, platform_set
                    FROM product_platforms
                    WHERE product_id = product.id;

                IF platform_count = 0 THEN
                    RAISE EXCEPTION 'product must cover at least one platform (product %)', product.id
                        USING ERRCODE = 'check_violation';
                END IF;

                IF product.type = 'physical' AND platform_count <> 1 THEN
                    RAISE EXCEPTION 'physical product must cover exactly one platform (product %)', product.id
                        USING ERRCODE = 'check_violation';
                END IF;

                IF EXISTS (
                    SELECT 1
                    FROM products other
                    WHERE other.id <> product.id
                        AND other.title_id = product.title_id
                        AND other.type = product.type
                        AND other.activation_service_id IS NOT DISTINCT FROM product.activation_service_id
                        AND other.region_id IS NOT DISTINCT FROM product.region_id
                        AND ARRAY(
                            SELECT platform_id
                            FROM product_platforms
                            WHERE product_id = other.id
                            ORDER BY platform_id
                        ) = platform_set
                ) THEN
                    RAISE EXCEPTION 'duplicate product identity (product %)', product.id
                        USING ERRCODE = 'unique_violation';
                END IF;
            END;
            $$;

            CREATE OR REPLACE FUNCTION products_check_identity() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_TABLE_NAME = 'products' THEN
                    PERFORM products_assert_identity(NEW.id);

                    RETURN NULL;
                END IF;

                IF TG_OP IN ('INSERT', 'UPDATE') THEN
                    PERFORM products_assert_identity(NEW.product_id);
                END IF;

                IF TG_OP = 'DELETE' THEN
                    PERFORM products_assert_identity(OLD.product_id);
                ELSIF TG_OP = 'UPDATE' THEN
                    IF OLD.product_id <> NEW.product_id THEN
                        PERFORM products_assert_identity(OLD.product_id);
                    END IF;
                END IF;

                RETURN NULL;
            END;
            $$;

            CREATE CONSTRAINT TRIGGER products_identity
                AFTER INSERT OR UPDATE OF title_id, type, activation_service_id, region_id ON products
                DEFERRABLE INITIALLY DEFERRED
                FOR EACH ROW EXECUTE FUNCTION products_check_identity();

            CREATE CONSTRAINT TRIGGER product_platforms_identity
                AFTER INSERT OR UPDATE OR DELETE ON product_platforms
                DEFERRABLE INITIALLY DEFERRED
                FOR EACH ROW EXECUTE FUNCTION products_check_identity();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS product_platforms_identity ON product_platforms;
            DROP TRIGGER IF EXISTS products_identity ON products;
            DROP FUNCTION IF EXISTS products_check_identity();
            DROP FUNCTION IF EXISTS products_assert_identity(bigint);
            SQL);
    }
};
