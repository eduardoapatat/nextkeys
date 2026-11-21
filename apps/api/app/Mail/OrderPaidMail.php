<?php

namespace App\Mail;

use App\Enums\PaymentAttemptStatus;
use App\Enums\ProductType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\Platform;
use App\Models\ProductKey;
use Carbon\CarbonInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Config;

/**
 * The payment confirmation, with the keys of every key-delivered line
 * (docs/domain/orders.md, "Confirmation email and key delivery").
 *
 * Never queued (no ShouldQueue): only SendOrderPaidEmail, which carries the
 * order id, goes through the queue. The codes are decrypted here, while
 * rendering, and live only in the view data.
 */
final class OrderPaidMail extends Mailable
{
    /**
     * Dates are stored in UTC and shown to Chilean customers.
     */
    private const string DISPLAY_TIMEZONE = 'America/Santiago';

    public function __construct(private readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Tu pedido {$this->order->reference} está pagado",
        );
    }

    public function content(): Content
    {
        $this->order->load([
            'items' => fn ($query) => $query->orderBy('id'),
            'items.keys' => fn ($query) => $query->select(['id', 'order_item_id', 'code'])->orderBy('id'),
            'items.product.activationService',
            'items.product.platforms',
            'items.product.region',
            'paymentAttempts' => fn ($query) => $query->where('status', PaymentAttemptStatus::Authorized),
        ]);

        $attempt = $this->order->paymentAttempts->first();

        return new Content(
            markdown: 'mail.orders.paid',
            with: [
                'reference' => $this->order->reference,
                'paidAt' => $this->formatDate($this->order->paid_at),
                'total' => $this->formatMoney($this->order->total),
                'lines' => $this->order->items->map(fn (OrderItem $item): array => [
                    'title' => $item->title_name,
                    'type' => $item->product_type->label(),
                    'quantity' => $item->quantity,
                    'unitPrice' => $this->formatMoney($item->unit_price),
                    'lineTotal' => $this->formatMoney($item->line_total),
                    'ships' => $item->product_type === ProductType::Physical,
                    'activation' => $this->activationOf($item),
                    'codes' => $item->keys->map(fn (ProductKey $key): string => $key->code)->all(),
                ])->all(),
                'hasPhysicalItems' => $this->order->items->contains('product_type', ProductType::Physical),
                'receipt' => $attempt === null ? null : $this->receiptOf($attempt),
                'libraryUrl' => Config::string('storefront.url').'/biblioteca',
            ],
        );
    }

    /**
     * Where a key is activated: its activation service or, without one, its
     * platforms, plus the region.
     */
    private function activationOf(OrderItem $item): ?string
    {
        if ($item->product_type === ProductType::Physical) {
            return null;
        }

        $product = $item->product;
        $where = $product->activationService->name
            ?? $product->platforms->map(fn (Platform $platform): string => $platform->name)->join(', ');

        return $product->region === null ? $where : "{$where} · región {$product->region->name}";
    }

    /**
     * @return array{authorizationCode: string|null, paymentType: string|null, card: string|null, date: string|null, amount: string}
     */
    private function receiptOf(PaymentAttempt $attempt): array
    {
        return [
            'authorizationCode' => $attempt->authorization_code,
            'paymentType' => $attempt->paymentTypeLabel(),
            'card' => $attempt->card_last_digits === null ? null : "•••• {$attempt->card_last_digits}",
            'date' => $this->formatDate($attempt->transaction_date),
            'amount' => $this->formatMoney($attempt->amount),
        ];
    }

    private function formatMoney(int $amount): string
    {
        return '$'.number_format($amount, 0, ',', '.');
    }

    private function formatDate(?CarbonInterface $date): ?string
    {
        return $date?->copy()->setTimezone(self::DISPLAY_TIMEZONE)->locale('es')->isoFormat('D [de] MMMM [de] YYYY, HH:mm');
    }
}
