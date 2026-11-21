<?php

namespace App\Actions\Catalog;

use App\Catalog\CoverArt\CoverArtSource;
use App\Catalog\CoverArt\CoverArtUnavailable;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Gives products the official cover of their title from the cover art
 * source: one lookup per title, one file per product. Products that already
 * have a cover are left alone unless $force. A product keeps its old cover
 * until the new one is saved; the replaced file is deleted afterwards.
 */
final class ImportCoverArt
{
    public function __construct(
        private readonly CoverArtSource $source,
        private readonly StoreProductCover $covers,
    ) {}

    public function isAvailable(): bool
    {
        return $this->source->isConfigured();
    }

    /**
     * @return array<string, 'imported'|'not_found'> Outcome per title name.
     *
     * @throws CoverArtUnavailable
     */
    public function handle(bool $force = false, ?string $title = null): array
    {
        $products = Product::query()
            ->with('title')
            ->when(! $force, fn ($query) => $query->whereNull('cover_image'))
            ->when($title !== null, fn ($query) => $query->whereRelation('title', 'name', $title))
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Product $product): string => $product->title->name);

        $outcomes = [];

        foreach ($products as $name => $titleProducts) {
            $image = $this->source->coverFor((string) $name);

            if ($image === null) {
                $outcomes[(string) $name] = 'not_found';

                continue;
            }

            foreach ($titleProducts as $product) {
                $this->replaceCover($product, $image);
            }

            $outcomes[(string) $name] = 'imported';
        }

        return $outcomes;
    }

    private function replaceCover(Product $product, string $image): void
    {
        $previous = $product->cover_image;
        $path = $this->covers->fromJpeg($image);

        try {
            $product->update(['cover_image' => $path, 'cover_band' => true]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);

            throw $exception;
        }

        if ($previous !== null) {
            Storage::disk('public')->delete($previous);
        }
    }
}
