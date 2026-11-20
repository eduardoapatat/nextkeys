<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\InsufficientStock;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\OrderCannotBePaid;
use App\DataTables\OrdersTable;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('orders/Index', [
            'orders' => fn () => OrdersTable::make(),
        ]);
    }

    /**
     * Keys are listed by hint and status only: the code never reaches the panel.
     */
    public function show(Order $order): Response
    {
        $order->load([
            'user:id,name,email',
            'items' => fn ($query) => $query->orderBy('id'),
            'items.keys' => fn ($query) => $query->select(['id', 'order_item_id', 'code_hint', 'status'])->orderBy('id'),
        ]);

        return Inertia::render('orders/Show', [
            'order' => [
                'reference' => $order->reference,
                'status' => [
                    'value' => $order->status->value,
                    'label' => $order->status->label(),
                    'variant' => $order->status->badgeVariant(),
                ],
                'total' => $order->total,
                'customer' => ['name' => $order->user->name, 'email' => $order->user->email],
                'created_at' => $order->created_at?->toIso8601String(),
                'expires_at' => $order->expires_at->toIso8601String(),
                'paid_at' => $order->paid_at?->toIso8601String(),
                'completed_at' => $order->completed_at?->toIso8601String(),
                'cancelled_at' => $order->cancelled_at?->toIso8601String(),
                'items' => $order->items->map(fn (OrderItem $item): array => [
                    'id' => $item->id,
                    'title_name' => $item->title_name,
                    'type' => ['value' => $item->product_type->value, 'label' => $item->product_type->label()],
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'keys' => $item->keys->map(fn (ProductKey $key): array => [
                        'id' => $key->id,
                        'code_hint' => $key->code_hint,
                        'status' => $key->status->label(),
                    ])->all(),
                ])->all(),
            ],
            'canCancel' => $order->status === OrderStatus::Pending,
            'canMarkPaid' => $this->manualPaymentsEnabled()
                && in_array($order->status, [OrderStatus::Pending, OrderStatus::Expired], true),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function cancel(Order $order, CancelOrder $cancelOrder): RedirectResponse
    {
        $cancelOrder->handle($order);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pedido cancelado.']);

        return to_route('orders.show', $order);
    }

    /**
     * Records a payment by hand, to try orders without a payment gateway.
     * Only when `orders.manual_payments` is on (local): in production it
     * would deliver keys without charging.
     *
     * @throws ValidationException
     */
    public function markPaid(Order $order, MarkOrderPaid $markOrderPaid): RedirectResponse
    {
        abort_unless($this->manualPaymentsEnabled(), 404);

        try {
            $markOrderPaid->handle($order);
        } catch (OrderCannotBePaid $exception) {
            throw ValidationException::withMessages([
                'order' => $exception->getPrevious() instanceof InsufficientStock
                    ? 'El pedido no se puede pagar: expiró y ya no hay stock para reservarlo.'
                    : 'El pedido no se puede pagar: está cancelado.',
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pago registrado.']);

        return to_route('orders.show', $order);
    }

    private function manualPaymentsEnabled(): bool
    {
        return Config::boolean('orders.manual_payments');
    }
}
