<?php

namespace App\Http\Requests\Catalog;

use App\Actions\Catalog\ProductData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Checks only the shape of the product form. The catalog rules (per type,
 * identity, slug) live in the Actions, so every write path applies them.
 */
class UpdateProductRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title_id' => ['required', 'integer'],
            'activation_service_id' => ['nullable', 'integer'],
            'region_id' => ['nullable', 'integer'],
            'platform_ids' => ['nullable', 'array'],
            'platform_ids.*' => ['integer'],
            'price' => ['required', 'integer'],
            'stock' => ['nullable', 'integer'],
            'slug' => ['nullable', 'string', 'max:255'],
            'cover' => ['nullable', 'file'],
            'remove_cover' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): ProductData
    {
        return new ProductData(
            titleId: $this->integer('title_id'),
            activationServiceId: $this->filled('activation_service_id') ? $this->integer('activation_service_id') : null,
            regionId: $this->filled('region_id') ? $this->integer('region_id') : null,
            platformIds: array_values(array_map(fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $this->array('platform_ids'))),
            price: $this->integer('price'),
            stock: $this->filled('stock') ? $this->integer('stock') : null,
            isPublished: $this->boolean('is_published'),
            slug: $this->filled('slug') ? $this->string('slug')->toString() : null,
            cover: $this->file('cover') instanceof UploadedFile ? $this->file('cover') : null,
            removeCover: $this->boolean('remove_cover'),
        );
    }
}
