<?php

namespace App\Actions\Catalog;

use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Product;
use App\Models\Region;
use App\Models\Title;
use Illuminate\Support\Str;

/**
 * Builds the slug suggested for a new product (rule in docs/domain/catalog.md,
 * "Slugs"): title name + activation service (or `physical` + activation
 * service) + platforms + region, skipping null parts.
 */
final class GenerateProductSlug
{
    /**
     * @param  iterable<int, Platform>  $platforms
     */
    public function suggest(Title $title, ProductType $type, ?ActivationService $activationService, iterable $platforms, ?Region $region): string
    {
        $platformSlugs = collect($platforms)->map(fn (Platform $platform): string => $platform->slug)->sort()->values();

        return collect([
            Str::slug($title->name),
            $type === ProductType::Physical ? 'physical' : null,
            $activationService?->slug,
            ...$platformSlugs,
            $region?->slug,
        ])->filter(fn (?string $part): bool => $part !== null && $part !== '')->implode('-');
    }

    /**
     * The given slug if no product uses it, otherwise the first free `-2`, `-3`… variant.
     */
    public function unique(string $slug): string
    {
        $taken = Product::query()
            ->where('slug', $slug)
            ->orWhere('slug', 'like', $slug.'-%')
            ->pluck('slug')
            ->flip();

        if (! $taken->has($slug)) {
            return $slug;
        }

        $suffix = 2;

        while ($taken->has("{$slug}-{$suffix}")) {
            $suffix++;
        }

        return "{$slug}-{$suffix}";
    }
}
