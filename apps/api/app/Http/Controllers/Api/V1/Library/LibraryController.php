<?php

namespace App\Http\Controllers\Api\V1\Library;

use App\Actions\Orders\RevealOrderLineKeys;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LibraryItemResource;
use App\Models\OrderItem;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * "My library": the customer's keys from paid orders. The listing never
 * carries a code; reveal() returns the codes of one line on request.
 */
class LibraryController extends Controller
{
    private const int PER_PAGE = 20;

    public function index(#[CurrentUser] User $customer): AnonymousResourceCollection
    {
        $items = OrderItem::query()
            ->select('order_items.*')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $customer->id)
            ->whereIn('orders.status', [OrderStatus::Paid, OrderStatus::Completed])
            ->whereIn('order_items.product_type', [ProductType::DigitalKey, ProductType::License])
            ->orderByDesc('orders.paid_at')
            ->orderBy('order_items.id')
            ->with([
                'order',
                'keys' => fn ($query) => $query->select(['id', 'order_item_id', 'code_hint', 'revealed_at'])->orderBy('id'),
                'product.platforms',
                'product.region',
                'product.activationService',
            ])
            ->paginate(self::PER_PAGE);

        return LibraryItemResource::collection($items);
    }

    public function reveal(#[CurrentUser] User $customer, string $reference, string $slug, RevealOrderLineKeys $revealOrderLineKeys): JsonResponse
    {
        $keys = $revealOrderLineKeys->handle($customer, $reference, $slug);

        return response()
            ->json(['data' => ['keys' => $keys->map(fn (ProductKey $key): array => [
                'hint' => $key->code_hint,
                'code' => $key->code,
            ])->values()->all()]])
            ->header('Cache-Control', 'no-store, private');
    }
}
