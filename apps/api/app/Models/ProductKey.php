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
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

/**
 * A code to deliver for a `digital_key` or `license` product.
 *
 * @property int $id
 * @property int $product_id
 * @property ProductType $product_type
 * @property string $code
 * @property string $code_hash
 * @property string $code_hint
 * @property ProductKeyStatus $status
 * @property int|null $order_item_id Set exactly while the key is reserved or sold.
 * @property Carbon|null $revealed_at First time the customer revealed it in "My library" (sold keys only).
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read OrderItem|null $orderItem
 */
#[Fillable(['product_id', 'product_type', 'code', 'status', 'order_item_id'])]
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
     * A mask of the code that is safe to show, e.g. `XXXXX-XXXXX-XGHIJ`: every
     * letter and digit becomes `X` except the last 4, and never more than a
     * third of them, so short codes are not almost exposed. Hyphens are kept.
     */
    public static function hintFor(string $code): string
    {
        $characters = mb_str_split(trim($code));
        $alphanumeric = count(array_filter($characters, fn (string $character): bool => ctype_alnum($character)));
        $visible = min(4, intdiv($alphanumeric, 3));
        $hidden = $alphanumeric - $visible;

        $hint = '';

        foreach ($characters as $character) {
            if (ctype_alnum($character) && $hidden > 0) {
                $hint .= 'X';
                $hidden--;
            } else {
                $hint .= $character;
            }
        }

        return $hint;
    }

    /**
     * The stored columns of a code: the trimmed code encrypted, its hash and
     * its hint. The only way a code reaches the database, through the cast or
     * a bulk insert, so a code is never stored without its hash and hint.
     *
     * @return array{code: string, code_hash: string, code_hint: string}
     */
    public static function storedCodeAttributes(string $code): array
    {
        return [
            'code' => Crypt::encryptString(trim($code)),
            'code_hash' => self::hashCode($code),
            'code_hint' => self::hintFor($code),
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
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
            'revealed_at' => 'datetime',
        ];
    }
}
