<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the `is_published` flag with a status (docs/domain/catalog.md,
     * "Product status"): published products stay published, the rest become
     * drafts.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('status')->default('draft');
        });

        DB::statement("UPDATE products SET status = CASE WHEN is_published THEN 'published' ELSE 'draft' END");

        DB::statement(<<<'SQL'
            ALTER TABLE products
                ADD CONSTRAINT products_status_check
                    CHECK (status IN ('draft', 'published', 'archived'))
            SQL);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_published')->default(false);
        });

        DB::statement("UPDATE products SET is_published = (status = 'published')");

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
