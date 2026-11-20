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
use Illuminate\Support\Carbon;

/**
 * One checkout of a customer, paid once. See docs/domain/orders.md.
 *
 * @property int $id
 * @property int $user_id
 * @property string $reference Public identifier, never the id.
 * @property OrderStatus $status
 * @property int $total
 * @property Carbon $expires_at End of the stock reservation while pending.
 * @property Carbon|null $paid_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, OrderItem> $items
 */
#[Fillable(['user_id', 'reference', 'status', 'total', 'expires_at', 'paid_at', 'completed_at', 'cancelled_at'])]
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
