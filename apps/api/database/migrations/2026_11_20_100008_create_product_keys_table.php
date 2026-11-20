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
        Schema::create('product_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->string('product_type');
            $table->text('code');
            $table->char('code_hash', 64)->unique();
            $table->string('status')->default('available');
            $table->timestamps();

            $table->index(['product_id', 'status']);

            // Only key-delivered products can have keys: the key repeats its
            // product's type and must match it, and that type is restricted below.
            $table->foreign(['product_id', 'product_type'])
                ->references(['id', 'type'])
                ->on('products')
                ->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE product_keys
                ADD CONSTRAINT product_keys_product_type_check
                    CHECK (product_type IN ('digital_key', 'license')),
                ADD CONSTRAINT product_keys_status_check
                    CHECK (status IN ('available', 'reserved', 'sold'))
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_keys');
    }
};
