<?php

use App\Enums\ChileRegion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shipping addresses saved in a customer's account (docs/domain/shipping.md,
 * "Customer addresses"). Orders copy them, so they can be edited or deleted.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->ulid('public_id')->unique();
            $table->string('recipient_name');
            $table->string('phone');
            $table->string('region');
            $table->string('commune');
            $table->string('street');
            $table->string('street_number');
            $table->string('apartment')->nullable();
            $table->timestamps();
        });

        $regions = implode(', ', array_map(fn (ChileRegion $region): string => "'{$region->value}'", ChileRegion::cases()));

        DB::statement("ALTER TABLE customer_addresses ADD CONSTRAINT customer_addresses_region_check CHECK (region IN ({$regions}))");
        DB::statement("ALTER TABLE customer_addresses ADD CONSTRAINT customer_addresses_phone_format CHECK (phone ~ '^\\+569[0-9]{8}$')");
        DB::statement("ALTER TABLE customer_addresses ADD CONSTRAINT customer_addresses_text_not_blank CHECK (btrim(recipient_name) <> '' AND btrim(commune) <> '' AND btrim(street) <> '' AND btrim(street_number) <> '' AND (apartment IS NULL OR btrim(apartment) <> ''))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
