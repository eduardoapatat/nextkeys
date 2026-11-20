<?php

namespace App\Models;

use App\Enums\ProductKeyStatus;
use App\Enums\ProductType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * What gets sold: one way of selling a title (type, activation service,
 * region and set of platforms). See docs/domain/catalog.md.
 *
 * @property int $id
 * @property int $title_id
 * @property ProductType $type
 * @property string $slug
 * @property int|null $activation_service_id
 * @property int|null $region_id
 * @property int $price
 * @property int|null $stock
 * @property bool $is_published
 * @property string|null $cover_image Path on the `public` disk.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Title $title
 * @property-read ActivationService|null $activationService
 * @property-read Region|null $region
 */
#[Fillable(['title_id', 'type', 'slug', 'activation_service_id', 'region_id', 'price', 'stock', 'is_published', 'cover_image'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Units that can be sold now: available keys for key-delivered types,
     * the `stock` column for physical products.
     */
    public function availableStock(): int
    {
        if ($this->type->isDeliveredByKey()) {
            return $this->keys()->where('status', ProductKeyStatus::Available)->count();
        }

        return $this->stock ?? 0;
    }

    /**
     * @return BelongsTo<Title, $this>
     */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    /**
     * @return BelongsTo<ActivationService, $this>
     */
    public function activationService(): BelongsTo
    {
        return $this->belongsTo(ActivationService::class);
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * The set of platforms the purchase covers.
     *
     * @return BelongsToMany<Platform, $this>
     */
    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class, 'product_platforms');
    }

    /**
     * Public URL of the cover, or null when the product has none yet.
     */
    public function coverUrl(): ?string
    {
        return $this->cover_image === null ? null : Storage::disk('public')->url($this->cover_image);
    }

    /**
     * @return HasMany<ProductKey, $this>
     */
    public function keys(): HasMany
    {
        return $this->hasMany(ProductKey::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'price' => 'integer',
            'stock' => 'integer',
            'is_published' => 'boolean',
        ];
    }
}
