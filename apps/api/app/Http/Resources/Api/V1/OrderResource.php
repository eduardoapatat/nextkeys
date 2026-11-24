<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

/**
 * A customer's order. `last_payment` carries the receipt Transbank asks the
 * result page to show. Never keys, ids or the gateway's `buy_order`.
 *
 * Expects `items.product`, `paymentAttempts` and `shipment` loaded.
 *
 * @property Order $resource
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $order = $this->resource;
        $lastPayment = $order->paymentAttempts->sortByDesc('number')->first();

        return [
            'reference' => $order->reference,
            'status' => ['slug' => $order->status->value, 'name' => $order->status->label()],
            'total' => $order->total,
            'shipping_cost' => $order->shipping_cost,
            'created_at' => $order->created_at?->toIso8601String(),
            'expires_at' => $order->expires_at->toIso8601String(),
            'paid_at' => $order->paid_at?->toIso8601String(),
            'can_pay' => $order->status === OrderStatus::Pending
                && $order->paymentAttempts->count() < Config::integer('payments.max_attempts'),
            'has_physical_items' => $order->items->contains(fn (OrderItem $item): bool => $item->product_type === ProductType::Physical),
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'product_slug' => $item->product->slug,
                'title_name' => $item->title_name,
                'type' => ['slug' => $item->product_type->value, 'name' => $item->product_type->label()],
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ])->values()->all(),
            'last_payment' => $lastPayment instanceof PaymentAttempt ? [
                'status' => ['slug' => $lastPayment->status->value, 'name' => $lastPayment->status->label()],
                'amount' => $lastPayment->amount,
                'authorization_code' => $lastPayment->authorization_code,
                'payment_type' => $lastPayment->paymentTypeLabel(),
                'installments_number' => $lastPayment->installments_number,
                'installments_amount' => $lastPayment->installments_amount,
                'card_last_digits' => $lastPayment->card_last_digits,
                'transaction_date' => $lastPayment->transaction_date?->toIso8601String(),
            ] : null,
            'shipment' => $order->shipment === null ? null : (new ShipmentResource($order->shipment))->toArray($request),
        ];
    }
}
