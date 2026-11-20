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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedBigInteger('line_total');
            $table->string('title_name');
            $table->string('product_type');
            $table->timestamps();

            $table->unique(['order_id', 'product_id']);

            // Target of the composite FK on product_keys: a key can only be
            // linked to a line of its own product.
            $table->unique(['id', 'product_id']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE order_items
                ADD CONSTRAINT order_items_quantity_range
                    CHECK (quantity BETWEEN 1 AND 5),
                ADD CONSTRAINT order_items_unit_price_positive
                    CHECK (unit_price > 0),
                ADD CONSTRAINT order_items_line_total_matches
                    CHECK (line_total = unit_price * quantity),
                ADD CONSTRAINT order_items_product_type_check
                    CHECK (product_type IN ('digital_key', 'license', 'physical'))
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
