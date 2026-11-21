<?php

use App\Actions\Orders\ExpireOrders;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Enums\ProductKeyStatus;
use App\Mail\OrderPaidMail;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->product = Product::factory()->published()->create(['price' => 19990]);
    $this->key = ProductKey::factory()->for($this->product)->create(['code' => 'SECRET-00001-ABCD']);
    $this->placeOrder = fn (?User $customer = null): Order => app(PlaceOrder::class)
        ->handle($customer ?? User::factory()->create(), [new OrderLineData($this->product->id, 1)]);
});

it('lists orders newest first', function () {
    $older = ($this->placeOrder)();
    $this->travel(1)->minutes();
    $newer = Order::factory()->completed()->create();

    $this->get(route('orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('orders/Index')
            ->where('orders.meta.total', 2)
            ->where('orders.data.0.reference', $newer->reference)
            ->where('orders.data.1.reference', $older->reference)
            ->where('orders.data.1.status', 'pending')
            ->where('orders.data.1.total', 19990));
});

it('filters orders by status', function () {
    ($this->placeOrder)();
    $completed = Order::factory()->completed()->create();

    $this->get(route('orders.index', ['filter' => ['status' => 'completed']]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('orders.meta.total', 1)
            ->where('orders.data.0.reference', $completed->reference));
});

it('searches orders by reference and by customer email', function (Closure $searchFor) {
    $order = ($this->placeOrder)(User::factory()->create(['email' => 'ana@example.com']));
    Order::factory()->create();

    $this->get(route('orders.index', ['search' => $searchFor($order)]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('orders.meta.total', 1)
            ->where('orders.data.0.reference', $order->reference));
})->with([
    'reference' => fn (Order $order): string => $order->reference,
    'email' => fn (Order $order): string => 'ana@example',
]);

it('shows an order by its reference with lines and key hints, never codes', function () {
    $order = ($this->placeOrder)();

    $this->get("/orders/{$order->reference}")
        ->assertOk()
        ->assertDontSee('SECRET-00001')
        ->assertInertia(fn (Assert $page) => $page
            ->component('orders/Show')
            ->where('order.reference', $order->reference)
            ->where('order.status.label', 'Pendiente')
            ->where('order.total', 19990)
            ->where('order.items.0.title_name', $this->product->title->name)
            ->where('order.items.0.type.label', $this->product->type->label())
            ->has('order.items.0.keys.0', fn (Assert $key) => $key
                ->where('code_hint', $this->key->code_hint)
                ->where('status', 'Reservada')
                ->where('revealed_at', null)
                ->missingAll(['code', 'code_hash'])
                ->etc())
            ->where('canCancel', true)
            ->where('canMarkPaid', false)
            ->where('canPayWithWebpay', false));
});

it('lists the payment attempts of an order with their receipt, never the token', function () {
    $order = Order::factory()->create();
    $refunded = PaymentAttempt::factory()->for($order)->refunded()->create();

    $this->get(route('orders.show', $order))
        ->assertDontSee((string) $refunded->token)
        ->assertInertia(fn (Assert $page) => $page
            ->has('order.payments.0', fn (Assert $payment) => $payment
                ->where('buy_order', "{$order->reference}-1")
                ->where('status.label', 'Reembolsado')
                ->where('authorization_code', '1213')
                ->where('card_last_digits', '6623')
                ->where('refunded_amount', $order->total)
                ->missing('token')
                ->etc()));
});

it('hides the Webpay test payment unless manual payments are enabled', function () {
    $order = ($this->placeOrder)();
    session()->flash('webpay_redirect', ['url' => 'https://webpay.test', 'token' => webpayToken()]);

    $this->post(route('orders.pay-with-webpay', $order))->assertNotFound();
    $this->get(route('orders.webpay-redirect', $order))->assertNotFound();

    expect($order->paymentAttempts()->count())->toBe(0);
});

it('does not find an order by its id', function () {
    $order = ($this->placeOrder)();

    $this->get("/orders/{$order->id}")->assertNotFound();
});

it('cancels a pending order', function () {
    $order = ($this->placeOrder)();

    $this->post(route('orders.cancel', $order))
        ->assertRedirect(route('orders.show', $order))
        ->assertInertiaFlash('toast.message', 'Pedido cancelado.');

    expect($order->fresh()?->status)->toBe(OrderStatus::Cancelled)
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Available);
});

it('refuses to cancel an order that is not pending', function () {
    $order = Order::factory()->completed()->create();

    $this->from(route('orders.show', $order))
        ->post(route('orders.cancel', $order))
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasErrors(['order' => 'Solo se puede cancelar un pedido pendiente.']);
});

it('hides manual payments unless they are enabled', function () {
    $order = ($this->placeOrder)();

    $this->post(route('orders.mark-paid', $order))->assertNotFound();

    expect($order->fresh()?->status)->toBe(OrderStatus::Pending);
});

describe('with manual payments enabled', function () {
    beforeEach(fn () => config(['orders.manual_payments' => true]));

    it('offers to mark pending orders as paid', function () {
        $order = ($this->placeOrder)();

        $this->get(route('orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page->where('canMarkPaid', true));
    });

    it('marks an order as paid', function () {
        $order = ($this->placeOrder)();

        $this->post(route('orders.mark-paid', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertInertiaFlash('toast.message', 'Pago registrado.');

        expect($order->fresh()?->status)->toBe(OrderStatus::Completed)
            ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Sold);
    });

    it('explains why an expired order without stock cannot be paid', function () {
        $order = ($this->placeOrder)();
        $this->travel(16)->minutes();
        app(ExpireOrders::class)->handle();
        ($this->placeOrder)();

        $this->from(route('orders.show', $order))
            ->post(route('orders.mark-paid', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors(['order' => 'El pedido no se puede pagar: expiró y ya no hay stock para reservarlo.']);
    });

    it('starts a Webpay test payment and posts its token to Webpay', function () {
        Http::fake([webpayUrl() => Http::response([
            'token' => webpayToken(),
            'url' => 'https://webpay3gint.transbank.cl/webpayserver/initTransaction',
        ])]);
        $order = ($this->placeOrder)();

        $this->get(route('orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page->where('canPayWithWebpay', true));

        $this->post(route('orders.pay-with-webpay', $order), [], ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('orders.webpay-redirect', $order));

        $this->get(route('orders.webpay-redirect', $order))
            ->assertOk()
            ->assertSee('action="https://webpay3gint.transbank.cl/webpayserver/initTransaction"', escape: false)
            ->assertSee('name="token_ws" value="'.webpayToken().'"', escape: false);
        expect($order->paymentAttempts()->sole()->token)->toBe(webpayToken());
    });

    it('explains why the Webpay test payment could not start', function () {
        Http::fake([webpayUrl() => Http::response(['error_message' => 'Internal error'], 500)]);
        $order = ($this->placeOrder)();

        $this->from(route('orders.show', $order))
            ->post(route('orders.pay-with-webpay', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors(['order' => 'No se pudo iniciar el pago con Webpay. Inténtalo de nuevo.']);
    });

    it('explains why a cancelled order cannot be paid', function () {
        $order = Order::factory()->cancelled()->create();

        $this->from(route('orders.show', $order))
            ->post(route('orders.mark-paid', $order))
            ->assertSessionHasErrors(['order' => 'El pedido no se puede pagar: está cancelado.']);
    });
});

it('is forbidden to customers', function () {
    $order = ($this->placeOrder)();
    $this->actingAs(User::factory()->create());

    $this->get(route('orders.index'))->assertForbidden();
    $this->get(route('orders.show', $order))->assertForbidden();
    $this->post(route('orders.cancel', $order))->assertForbidden();
});

describe('resending the payment confirmation', function () {
    beforeEach(function () {
        Mail::fake();
        $this->paidOrder = ($this->placeOrder)();
        app(MarkOrderPaid::class)->handle($this->paidOrder);
    });

    it('shows when the email went out and offers to resend it', function () {
        $this->get(route('orders.show', $this->paidOrder))
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.paid_email.resends', 0)
                ->where('order.paid_email.max_resends', 3)
                ->whereType('order.paid_email.sent_at', 'string')
                ->where('canResendPaidEmail', true));
    });

    it('resends it to the customer and counts the resend', function () {
        $this->post(route('orders.resend-paid-email', $this->paidOrder))
            ->assertRedirect(route('orders.show', $this->paidOrder))
            ->assertInertiaFlash('toast.message', 'Email de confirmación reenviado.');

        Mail::assertSent(OrderPaidMail::class, 2);
        expect($this->paidOrder->fresh()?->paid_email_resends)->toBe(1);
    });

    it('stops after three resends', function () {
        $this->paidOrder->forceFill(['paid_email_resends' => 3])->save();

        $this->from(route('orders.show', $this->paidOrder))
            ->post(route('orders.resend-paid-email', $this->paidOrder))
            ->assertSessionHasErrors(['order' => 'El email ya se reenvió 3 veces.']);

        Mail::assertSent(OrderPaidMail::class, 1);
        $this->get(route('orders.show', $this->paidOrder))
            ->assertInertia(fn (Assert $page) => $page->where('canResendPaidEmail', false));
    });

    it('allows one resend a minute', function () {
        $this->post(route('orders.resend-paid-email', $this->paidOrder))->assertRedirect();
        $this->post(route('orders.resend-paid-email', $this->paidOrder))->assertTooManyRequests();

        $this->travel(61)->seconds();
        $this->post(route('orders.resend-paid-email', $this->paidOrder))->assertRedirect();
        expect($this->paidOrder->fresh()?->paid_email_resends)->toBe(2);
    });
});

it('does not resend the email of an unpaid order', function () {
    Mail::fake();
    $order = ($this->placeOrder)();

    $this->from(route('orders.show', $order))
        ->post(route('orders.resend-paid-email', $order))
        ->assertSessionHasErrors(['order' => 'Solo se puede reenviar el email de un pedido pagado.']);

    Mail::assertNothingSent();
    $this->get(route('orders.show', $order))
        ->assertInertia(fn (Assert $page) => $page->where('canResendPaidEmail', false));
});
