<?php

namespace App\Models;

use App\Enums\Carrier;
use App\Enums\ChileRegion;
use App\Enums\ShipmentStatus;
use Carbon\CarbonInterface;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The parcel of an order with physical lines, with a copy of the address it
 * goes to (docs/domain/shipping.md). Editing or deleting the customer's
 * saved address never changes it.
 *
 * @property int $id
 * @property int $order_id
 * @property string $recipient_name
 * @property string $phone
 * @property ChileRegion $region
 * @property string $commune
 * @property string $street
 * @property string $street_number
 * @property string|null $apartment
 * @property ShipmentStatus $status
 * @property Carrier|null $carrier Set when it ships.
 * @property string|null $tracking_number Set when it ships.
 * @property Carbon|null $shipped_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order $order
 */
#[Fillable(['recipient_name', 'phone', 'region', 'commune', 'street', 'street_number', 'apartment', 'status'])]
class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    /**
     * Days the customer has to withdraw from a delivered purchase (derecho a
     * retracto, docs/domain/payments.md).
     */
    public const int WITHDRAWAL_DAYS = 10;

    /**
     * The snapshot of a saved address for a new shipment.
     *
     * @return array{recipient_name: string, phone: string, region: ChileRegion, commune: string, street: string, street_number: string, apartment: string|null, status: ShipmentStatus}
     */
    public static function attributesFrom(CustomerAddress $address): array
    {
        return [
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'region' => $address->region,
            'commune' => $address->commune,
            'street' => $address->street,
            'street_number' => $address->street_number,
            'apartment' => $address->apartment,
            'status' => ShipmentStatus::Pending,
        ];
    }

    /**
     * The address in one line, as a label would show it.
     */
    public function addressLine(): string
    {
        $street = "{$this->street} {$this->street_number}".($this->apartment === null ? '' : ", {$this->apartment}");

        return "{$street}, {$this->commune}, {$this->region->label()}";
    }

    public function trackingUrl(): ?string
    {
        return $this->carrier === null || $this->tracking_number === null
            ? null
            : $this->carrier->trackingUrl($this->tracking_number);
    }

    /**
     * Last day of the withdrawal period, counted from delivery.
     */
    public function returnableUntil(): ?CarbonInterface
    {
        return $this->delivered_at?->copy()->addDays(self::WITHDRAWAL_DAYS);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'region' => ChileRegion::class,
            'status' => ShipmentStatus::class,
            'carrier' => Carrier::class,
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
