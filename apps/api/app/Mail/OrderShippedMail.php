<?php

namespace App\Mail;

use App\Enums\ProductType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Config;

/**
 * The shipping notice: courier, tracking number and link, the address and
 * the shipped lines (docs/domain/shipping.md).
 *
 * Never queued (no ShouldQueue): only SendOrderShippedEmail, which carries
 * the order id, goes through the queue, so the address is never serialized.
 */
final class OrderShippedMail extends Mailable
{
    public function __construct(private readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Tu pedido {$this->order->reference} va en camino",
        );
    }

    public function content(): Content
    {
        $this->order->load([
            'items' => fn ($query) => $query->where('product_type', ProductType::Physical)->orderBy('id'),
            'shipment',
        ]);

        $shipment = $this->order->shipment;

        if (! $shipment instanceof Shipment || $shipment->carrier === null || $shipment->tracking_number === null) {
            throw new \LogicException("Order {$this->order->reference} has not shipped.");
        }

        return new Content(
            markdown: 'mail.orders.shipped',
            with: [
                'reference' => $this->order->reference,
                'carrier' => $shipment->carrier->label(),
                'trackingNumber' => $shipment->tracking_number,
                'trackingUrl' => $shipment->trackingUrl(),
                'recipient' => $shipment->recipient_name,
                'addressLine' => $shipment->addressLine(),
                'lines' => $this->order->items->map(fn (OrderItem $item): array => [
                    'title' => $item->title_name,
                    'quantity' => $item->quantity,
                ])->all(),
                'withdrawalDays' => Shipment::WITHDRAWAL_DAYS,
                'orderUrl' => Config::string('storefront.url')."/pedidos/{$this->order->reference}",
            ],
        );
    }
}
