<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\OrderBusy;
use App\Actions\Orders\OrderCannotBePaid;
use App\Actions\Orders\OrderCannotBePaidReason;
use App\Actions\Orders\ResendOrderPaidEmail;
use App\Actions\Payments\StartPaymentAttempt;
use App\DataTables\OrdersTable;
use App\Enums\OrderStatus;
use App\Enums\PaymentOrigin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\ProductKey;
use App\Payments\PaymentGatewayException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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
            'items.keys' => fn ($query) => $query->select(['id', 'order_item_id', 'code_hint', 'status', 'revealed_at'])->orderBy('id'),
            'paymentAttempts' => fn ($query) => $query->orderBy('number'),
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
                'paid_email' => [
                    'sent_at' => $order->paid_email_sent_at?->toIso8601String(),
                    'resends' => $order->paid_email_resends,
                    'max_resends' => Config::integer('orders.max_paid_email_resends'),
                ],
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
                        'revealed_at' => $key->revealed_at?->toIso8601String(),
                    ])->all(),
                ])->all(),
                'payments' => $order->paymentAttempts->map(fn (PaymentAttempt $attempt): array => [
                    'number' => $attempt->number,
                    'buy_order' => $attempt->buy_order,
                    'status' => [
                        'value' => $attempt->status->value,
                        'label' => $attempt->status->label(),
                        'variant' => $attempt->status->badgeVariant(),
                    ],
                    'amount' => $attempt->amount,
                    'authorization_code' => $attempt->authorization_code,
                    'payment_type' => $attempt->paymentTypeLabel(),
                    'card_last_digits' => $attempt->card_last_digits,
                    'created_at' => $attempt->created_at?->toIso8601String(),
                    'finished_at' => $attempt->finished_at?->toIso8601String(),
                    'refunded_amount' => $attempt->refunded_amount,
                    'refunded_at' => $attempt->refunded_at?->toIso8601String(),
                ])->all(),
            ],
            'canCancel' => $order->status === OrderStatus::Pending,
            'canResendPaidEmail' => in_array($order->status, [OrderStatus::Paid, OrderStatus::Completed], true)
                && $order->paid_email_resends < Config::integer('orders.max_paid_email_resends'),
            'canMarkPaid' => $this->manualPaymentsEnabled()
                && in_array($order->status, [OrderStatus::Pending, OrderStatus::Expired], true),
            'canPayWithWebpay' => $this->manualPaymentsEnabled()
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
                'order' => $exception->reason === OrderCannotBePaidReason::OutOfStock
                    ? 'El pedido no se puede pagar: expiró y ya no hay stock para reservarlo.'
                    : 'El pedido no se puede pagar: está cancelado.',
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pago registrado.']);

        return to_route('orders.show', $order);
    }

    /**
     * Resends the payment confirmation (with the keys) to the customer's
     * current email; never to another address.
     *
     * @throws ValidationException
     */
    public function resendPaidEmail(Order $order, ResendOrderPaidEmail $resendOrderPaidEmail): RedirectResponse
    {
        $resendOrderPaidEmail->handle($order);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Email de confirmación reenviado.']);

        return to_route('orders.show', $order);
    }

    /**
     * Starts a real Webpay payment in the integration environment, to try the
     * gateway end to end before the storefront checkout exists (phase 4).
     * Local only, like markPaid(). The browser then loads webpayRedirect(),
     * which posts the token to Webpay.
     *
     * @throws ValidationException
     */
    public function payWithWebpay(Order $order, StartPaymentAttempt $startPaymentAttempt): SymfonyResponse
    {
        abort_unless($this->manualPaymentsEnabled(), 404);

        try {
            $redirect = $startPaymentAttempt->handle($order, PaymentOrigin::Panel);
        } catch (OrderCannotBePaid $exception) {
            throw ValidationException::withMessages(['order' => match ($exception->reason) {
                OrderCannotBePaidReason::AlreadyPaid => 'El pedido ya está pagado.',
                OrderCannotBePaidReason::Cancelled => 'El pedido no se puede pagar: está cancelado.',
                OrderCannotBePaidReason::OutOfStock => 'El pedido no se puede pagar: expiró y ya no hay stock para reservarlo.',
            }]);
        } catch (OrderBusy|PaymentGatewayException) {
            throw ValidationException::withMessages(['order' => 'No se pudo iniciar el pago con Webpay. Inténtalo de nuevo.']);
        }

        session()->flash('webpay_redirect', ['url' => $redirect->url, 'token' => $redirect->token]);

        return Inertia::location(route('orders.webpay-redirect', $order));
    }

    /**
     * The page that posts the token to Webpay (a form, never an iframe).
     */
    public function webpayRedirect(Order $order): View
    {
        abort_unless($this->manualPaymentsEnabled(), 404);

        $redirect = session('webpay_redirect');
        abort_unless(is_array($redirect) && is_string($redirect['url'] ?? null) && is_string($redirect['token'] ?? null), 404);

        return view('payments.webpay-redirect', ['url' => $redirect['url'], 'token' => $redirect['token']]);
    }

    private function manualPaymentsEnabled(): bool
    {
        return Config::boolean('orders.manual_payments');
    }
}
