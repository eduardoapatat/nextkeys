<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the optional cover of each product (variant): a path on the
     * `public` disk.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('cover_image')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
    }
};
