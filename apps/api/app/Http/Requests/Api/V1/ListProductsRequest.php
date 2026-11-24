<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProductSort;
use App\Http\Requests\Api\V1\Concerns\ValidatesListingFacets;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProductsRequest extends FormRequest
{
    use ValidatesListingFacets;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->facetRules(),
            'sort' => ['string', Rule::enum(ProductSort::class)],
            'page' => ['integer', 'min:1'],
        ];
    }

    /**
     * Spanish names of the parameters, used in the generic rule messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sort' => 'orden',
            'page' => 'página',
        ];
    }
}
