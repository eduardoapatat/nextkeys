<?php

namespace App\Enums;

use App\Models\Product;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;

/**
 * Orders of the storefront listing. Every order ends with the product id, so
 * products that tie keep the same position across pages.
 */
enum ProductSort: string
{
    case Newest = 'newest';
    case Price = 'price';
    case PriceDescending = '-price';
    case Name = 'name';

    /**
     * @param  Builder<Product>  $query
     */
    public function apply(Builder $query): void
    {
        match ($this) {
            self::Newest => $query->orderByDesc('products.created_at')->orderByDesc('products.id'),
            self::Price => $query->orderBy('products.price')->orderBy('products.id'),
            self::PriceDescending => $query->orderByDesc('products.price')->orderBy('products.id'),
            self::Name => $query
                ->orderBy(Title::query()->selectRaw('lower(titles.name)')->whereColumn('titles.id', 'products.title_id'))
                ->orderBy('products.id'),
        };
    }
}
