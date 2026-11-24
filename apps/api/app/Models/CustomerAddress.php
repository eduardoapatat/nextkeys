<?php

namespace App\Models;

use App\Enums\ChileRegion;
use Database\Factories\CustomerAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A shipping address saved in a customer's account (docs/domain/shipping.md).
 * The API identifies it by `public_id`, never by `id`. Orders keep a copy.
 *
 * @property int $id
 * @property int $user_id
 * @property string $public_id
 * @property string $recipient_name
 * @property string $phone `+569XXXXXXXX`.
 * @property ChileRegion $region
 * @property string $commune One of the region's communes.
 * @property string $street
 * @property string $street_number
 * @property string|null $apartment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['recipient_name', 'phone', 'region', 'commune', 'street', 'street_number', 'apartment'])]
class CustomerAddress extends Model
{
    /** @use HasFactory<CustomerAddressFactory> */
    use HasFactory, HasUlids;

    /**
     * The ULID goes in `public_id`; `id` stays the internal key.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'region' => ChileRegion::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
