<?php

namespace App\Http\Requests\Catalog;

use App\Enums\ProductType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use LogicException;

/**
 * The update form plus the type, which is only chosen on creation.
 */
class StoreProductRequest extends UpdateProductRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ProductType::class)],
            ...parent::rules(),
        ];
    }

    public function type(): ProductType
    {
        return $this->enum('type', ProductType::class) ?? throw new LogicException('The type is validated as required.');
    }
}
