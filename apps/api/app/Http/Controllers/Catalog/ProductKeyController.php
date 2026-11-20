<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Catalog\ImportProductKeys;
use App\Actions\Catalog\KeyImportRejection;
use App\DataTables\ProductKeysTable;
use App\Enums\ProductKeyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ImportProductKeysRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductKeyController extends Controller
{
    /**
     * Rejected lines sent back to the page; the rest are only counted, so the
     * flash data stays small for a file of 5,000 bad lines.
     */
    private const int SHOWN_REJECTIONS = 100;

    public function index(Product $product): Response
    {
        abort_unless($product->type->isDeliveredByKey(), 404);

        return Inertia::render('catalog/products/keys/Index', [
            'product' => [
                'id' => $product->id,
                'slug' => $product->slug,
                'type' => $product->type->label(),
                'statusCounts' => array_map(
                    fn (ProductKeyStatus $status): array => [
                        'label' => $status->label(),
                        'total' => $product->keys()->where('status', $status)->count(),
                    ],
                    ProductKeyStatus::cases(),
                ),
            ],
            'keys' => fn () => ProductKeysTable::make(product: $product),
        ]);
    }

    public function store(ImportProductKeysRequest $request, Product $product, ImportProductKeys $importProductKeys): RedirectResponse
    {
        $result = $importProductKeys->handle($product, $request->contents());
        $rejectedCount = count($result->rejections);

        Inertia::flash('keyImport', [
            'imported' => $result->imported,
            'rejectedCount' => $rejectedCount,
            'rejections' => array_map(
                fn (KeyImportRejection $rejection): array => ['line' => $rejection->line, 'reason' => $rejection->reason->label()],
                array_slice($result->rejections, 0, self::SHOWN_REJECTIONS),
            ),
        ]);
        Inertia::flash('toast', $rejectedCount === 0
            ? ['type' => 'success', 'message' => "{$result->imported} keys importadas."]
            : ['type' => 'warning', 'message' => "{$result->imported} keys importadas, {$rejectedCount} rechazadas."]);

        return to_route('products.keys.index', $product);
    }
}
