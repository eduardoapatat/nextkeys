<?php

namespace App\Models;

use App\Casts\ProductKeyCode;
use App\Enums\ProductKeyStatus;
use App\Enums\ProductType;
use Database\Factories\ProductKeyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use RuntimeException;

/**
 * A code to deliver for a `digital_key` or `license` product.
 *
 * @property int $id
 * @property int $product_id
 * @property ProductType $product_type
 * @property string $code
 * @property string $code_hash
 * @property ProductKeyStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 */
#[Fillable(['product_id', 'product_type', 'code', 'status'])]
#[Hidden(['code', 'code_hash'])]
class ProductKey extends Model
{
    /** @use HasFactory<ProductKeyFactory> */
    use HasFactory;

    /**
     * HMAC-SHA256 of the normalized code (trimmed and uppercased), keyed with
     * `KEY_HASH_SECRET`, used to detect duplicates without decrypting.
     */
    public static function hashCode(string $code): string
    {
        $secret = Config::string('catalog.key_hash_secret', '');

        if ($secret === '') {
            throw new RuntimeException('KEY_HASH_SECRET is not set; product key codes cannot be hashed.');
        }

        return hash_hmac('sha256', mb_strtoupper(trim($code)), $secret);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'code' => ProductKeyCode::class,
            'status' => ProductKeyStatus::class,
        ];
    }
}
