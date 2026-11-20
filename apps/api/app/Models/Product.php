<?php

namespace App\Models;

use App\Enums\ProductKeyStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
 * @property ProductStatus $status
 * @property string|null $cover_image Path on the `public` disk.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Title $title
 * @property-read ActivationService|null $activationService
 * @property-read Region|null $region
 * @property-read bool|null $in_stock Only selected by the `withInStock()` scope.
 */
#[Fillable(['title_id', 'type', 'slug', 'activation_service_id', 'region_id', 'price', 'stock', 'status', 'cover_image'])]
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
     * Products the storefront shows.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('products.status', ProductStatus::Published);
    }

    /**
     * Adds the `in_stock` column: the SQL form of `availableStock() > 0`,
     * computed in the same query for every row.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withInStock(Builder $query): void
    {
        $keyTypes = array_values(array_map(
            fn (ProductType $type): string => $type->value,
            array_filter(ProductType::cases(), fn (ProductType $type): bool => $type->isDeliveredByKey()),
        ));

        if ($query->getQuery()->columns === null) {
            $query->select('products.*');
        }

        $query->selectRaw(
            'case when products.type in ('.implode(', ', array_fill(0, count($keyTypes), '?')).') '
            .'then exists (select 1 from product_keys where product_keys.product_id = products.id and product_keys.status = ?) '
            .'else coalesce(products.stock, 0) > 0 end as in_stock',
            [...$keyTypes, ProductKeyStatus::Available->value],
        );
    }

    /**
     * Published products with what the storefront shows of each one loaded:
     * `in_stock`, title, platforms, region and activation service.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function storefront(Builder $query): void
    {
        $query->published()
            ->withInStock()
            ->with(['title', 'platforms', 'region', 'activationService']);
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
            'status' => ProductStatus::class,
            'in_stock' => 'boolean',
        ];
    }
}
