<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * One checkout of a customer, paid once. See docs/domain/orders.md.
 *
 * @property int $id
 * @property int $user_id
 * @property string $reference Public identifier, never the id.
 * @property OrderStatus $status
 * @property int $total Lines plus `shipping_cost`.
 * @property int $shipping_cost Flat fee in CLP when the order ships, 0 otherwise.
 * @property Carbon $expires_at End of the stock reservation while pending.
 * @property Carbon|null $paid_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $paid_email_sent_at Last time the payment confirmation (with the keys) went out.
 * @property int $paid_email_resends Times the panel resent it, up to `orders.max_paid_email_resends`.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, PaymentAttempt> $paymentAttempts
 * @property-read Shipment|null $shipment
 */
#[Fillable(['user_id', 'reference', 'status', 'total', 'shipping_cost', 'expires_at', 'paid_at', 'completed_at', 'cancelled_at'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Characters of a reference: Crockford base32 (no I, L, O or U).
     */
    private const string REFERENCE_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * A random reference such as `NK-7QF2K9`. Not guaranteed unique: the
     * caller retries on a unique violation.
     */
    public static function generateReference(): string
    {
        $code = '';

        for ($i = 0; $i < 6; $i++) {
            $code .= self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)];
        }

        return 'NK-'.$code;
    }

    /**
     * Whether some line ships a box, so the order waits for shipping after
     * payment instead of completing.
     */
    public function hasPhysicalItems(): bool
    {
        return $this->items()->where('product_type', ProductType::Physical)->exists();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<PaymentAttempt, $this>
     */
    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'integer',
            'shipping_cost' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'paid_email_sent_at' => 'datetime',
            'paid_email_resends' => 'integer',
        ];
    }

    /**
     * The parcel of an order with physical lines (docs/domain/shipping.md).
     *
     * @return HasOne<Shipment, $this>
     */
    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }
}
