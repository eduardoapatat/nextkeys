<?php

namespace App\Actions\Catalog;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class DeleteProduct
{
    /**
     * Deletes a product nothing references (no keys yet), then its cover once
     * the row is gone; a referenced product is archived instead.
     *
     * The product row is locked first: inserting a key locks its product row
     * too (FOR KEY SHARE), so a concurrent import either already committed and
     * is seen by the check, or waits until the delete is done.
     *
     * @throws ValidationException
     */
    public function handle(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            Product::query()->whereKey($product->id)->lockForUpdate()->first();

            if ($product->keys()->exists()) {
                throw ValidationException::withMessages([
                    'product' => 'Este producto tiene claves y no se puede eliminar. Archívalo en su lugar.',
                ]);
            }

            $product->delete();
        });

        if ($product->cover_image !== null) {
            Storage::disk('public')->delete($product->cover_image);
        }
    }
}
