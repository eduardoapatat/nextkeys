<?php

use App\Models\ProductKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the hint shown in place of a code (docs/domain/catalog.md, "Variant
     * cover and key hint"), backfilled from the codes (decrypted by the
     * model's cast) before it becomes required.
     */
    public function up(): void
    {
        Schema::table('product_keys', function (Blueprint $table) {
            $table->string('code_hint', 100)->nullable()->after('code_hash');
        });

        ProductKey::query()->select(['id', 'code'])->chunkById(1000, function (Collection $keys): void {
            foreach ($keys as $key) {
                DB::table('product_keys')->where('id', $key->id)->update(['code_hint' => ProductKey::hintFor($key->code)]);
            }
        });

        Schema::table('product_keys', function (Blueprint $table) {
            $table->string('code_hint', 100)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_keys', function (Blueprint $table) {
            $table->dropColumn('code_hint');
        });
    }
};
