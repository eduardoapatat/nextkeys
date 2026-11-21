<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who started each payment attempt, so the Webpay return goes back to the
 * storefront or to the panel (docs/domain/payments.md, "Attempt origin").
 * Attempts from before the storefront checkout all came from the panel.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->string('origin')->default('panel');
        });

        DB::statement('ALTER TABLE payment_attempts ALTER COLUMN origin DROP DEFAULT');
        DB::statement("ALTER TABLE payment_attempts ADD CONSTRAINT payment_attempts_origin_check CHECK (origin IN ('storefront', 'panel'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->dropColumn('origin');
        });
    }
};
