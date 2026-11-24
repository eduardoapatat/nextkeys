<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\ValidatesListingFacets;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The filter options take the listing's current facets, to count each
 * option against them.
 */
class CatalogFiltersRequest extends FormRequest
{
    use ValidatesListingFacets;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->facetRules();
    }
}
