<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When the customer first revealed a sold key in "My library", for support
 * and warranty questions (docs/domain/orders.md, "My library").
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_keys', function (Blueprint $table): void {
            $table->timestamp('revealed_at')->nullable();
        });

        DB::statement("ALTER TABLE product_keys ADD CONSTRAINT product_keys_revealed_only_when_sold CHECK (revealed_at IS NULL OR status = 'sold')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_keys', function (Blueprint $table): void {
            $table->dropColumn('revealed_at');
        });
    }
};
