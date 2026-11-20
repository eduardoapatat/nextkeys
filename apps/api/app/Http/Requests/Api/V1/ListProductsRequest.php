<?php

namespace App\Http\Requests\Api\V1;

use App\Actions\Catalog\Storefront\ProductListingFilters;
use App\Enums\ProductSort;
use App\Enums\ProductType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProductsRequest extends FormRequest
{
    /**
     * Query parameters that take several comma-separated slugs.
     */
    private const array FACETS = ['platform', 'type', 'activation_service', 'region'];

    protected function prepareForValidation(): void
    {
        foreach (self::FACETS as $facet) {
            $value = $this->query($facet);

            if (is_string($value)) {
                $this->merge([$facet => array_values(array_filter(explode(',', $value), fn (string $slug): bool => $slug !== ''))]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'platform' => ['array'],
            'platform.*' => ['string', Rule::exists('platforms', 'slug')],
            'type' => ['array'],
            'type.*' => ['string', Rule::enum(ProductType::class)],
            'activation_service' => ['array'],
            'activation_service.*' => ['string', Rule::exists('activation_services', 'slug')],
            'region' => ['array'],
            'region.*' => ['string', Rule::exists('regions', 'slug')],
            'sort' => ['string', Rule::enum(ProductSort::class)],
            'page' => ['integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'platform.*' => 'La plataforma «:input» no existe.',
            'type.*' => 'El tipo «:input» no existe.',
            'activation_service.*' => 'El servicio de activación «:input» no existe.',
            'region.*' => 'La región «:input» no existe.',
        ];
    }

    public function filters(): ProductListingFilters
    {
        return new ProductListingFilters(
            platforms: $this->slugs('platform'),
            types: array_map(ProductType::from(...), $this->slugs('type')),
            activationServices: $this->slugs('activation_service'),
            regions: $this->slugs('region'),
            sort: $this->enum('sort', ProductSort::class) ?? ProductSort::Newest,
        );
    }

    /**
     * @return list<string>
     */
    private function slugs(string $facet): array
    {
        return array_values(array_filter($this->array($facet), is_string(...)));
    }
}
