<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The payment confirmation email, which carries the keys: when it last went
 * out and how many times the panel resent it (docs/domain/orders.md,
 * "Confirmation email and key delivery").
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('paid_email_sent_at')->nullable();
            $table->unsignedSmallInteger('paid_email_resends')->default(0);
        });

        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_paid_email_resends_check CHECK (paid_email_resends BETWEEN 0 AND 3)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['paid_email_sent_at', 'paid_email_resends']);
        });
    }
};
