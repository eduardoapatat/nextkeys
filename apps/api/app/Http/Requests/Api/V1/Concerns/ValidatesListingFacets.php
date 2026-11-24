<?php

namespace App\Http\Requests\Api\V1\Concerns;

use App\Actions\Catalog\Storefront\ProductListingFilters;
use App\Enums\ListingFacet;
use App\Enums\ProductSort;
use App\Enums\ProductType;
use Illuminate\Validation\Rule;

/**
 * The listing's facet parameters, shared by the product listing and the
 * filter options so both accept and reject exactly the same values. Each
 * facet takes several comma-separated slugs (or repeated keys).
 */
trait ValidatesListingFacets
{
    protected function prepareForValidation(): void
    {
        foreach (ListingFacet::cases() as $facet) {
            $value = $this->query($facet->value);

            if (is_string($value)) {
                $this->merge([$facet->value => array_values(array_filter(explode(',', $value), fn (string $slug): bool => $slug !== ''))]);
            }
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function facetRules(): array
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
            platforms: $this->slugs(ListingFacet::Platform),
            types: array_map(ProductType::from(...), $this->slugs(ListingFacet::Type)),
            activationServices: $this->slugs(ListingFacet::ActivationService),
            regions: $this->slugs(ListingFacet::Region),
            sort: $this->enum('sort', ProductSort::class) ?? ProductSort::Newest,
        );
    }

    /**
     * @return list<string>
     */
    private function slugs(ListingFacet $facet): array
    {
        return array_values(array_filter($this->array($facet->value), is_string(...)));
    }
}
