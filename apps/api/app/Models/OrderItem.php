<?php

namespace App\Models;

use App\Enums\ProductType;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One product in an order, with the price and name it had when ordered.
 *
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity
 * @property int $unit_price
 * @property int $line_total
 * @property string $title_name Snapshot of the title's name.
 * @property ProductType $product_type Snapshot of the product's type.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order $order
 * @property-read Product $product
 * @property-read Collection<int, ProductKey> $keys
 */
#[Fillable(['order_id', 'product_id', 'quantity', 'unit_price', 'line_total', 'title_name', 'product_type'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Keys reserved or sold for this line.
     *
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
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'line_total' => 'integer',
            'product_type' => ProductType::class,
        ];
    }
}
