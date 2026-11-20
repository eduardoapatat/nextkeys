<?php

namespace App\Casts;

use App\Models\ProductKey;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

/**
 * Encrypts a product key code (trimmed) and writes its `code_hash` in the
 * same step, so a code can never be stored without the hash that keeps it
 * unique.
 *
 * @implements CastsAttributes<string, string>
 */
class ProductKeyCode implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('The stored product key code must be an encrypted string.');
        }

        return Crypt::decryptString($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{code: string, code_hash: string}
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('A product key code must be a string.');
        }

        return ProductKey::storedCodeAttributes($value);
    }
}
