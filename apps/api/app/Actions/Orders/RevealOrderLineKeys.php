<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Models\OrderItem;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The codes of one key line of a customer's paid order, for "My library".
 * The first reveal of each key is recorded in `revealed_at`; later reveals
 * keep it. Codes are never logged (docs/domain/orders.md, "My library").
 */
final class RevealOrderLineKeys
{
    /**
     * @return Collection<int, ProductKey>
     *
     * @throws ModelNotFoundException when the customer has no such paid key line
     */
    public function handle(User $customer, string $reference, string $productSlug): Collection
    {
        $item = OrderItem::query()
            ->whereIn('product_type', [ProductType::DigitalKey, ProductType::License])
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', $customer->id)
                ->where('reference', $reference)
                ->whereIn('status', [OrderStatus::Paid, OrderStatus::Completed]))
            ->whereHas('product', fn ($query) => $query->where('slug', $productSlug))
            ->firstOrFail();

        $item->keys()->whereNull('revealed_at')->update(['revealed_at' => now()]);

        return $item->keys()->orderBy('id')->get();
    }
}
