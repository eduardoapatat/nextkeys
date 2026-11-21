<?php

namespace App\Actions\Catalog;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Writes a product cover to `covers/` on the `public` disk and returns its
 * path. Each product gets its own file, so deleting one never breaks another.
 */
final class StoreProductCover
{
    public function fromUpload(UploadedFile $cover): string
    {
        $path = Storage::disk('public')->putFile('covers', $cover);

        if ($path === false) {
            throw new RuntimeException('The product cover could not be stored.');
        }

        return $path;
    }

    public function fromJpeg(string $contents): string
    {
        $path = 'covers/'.Str::random(40).'.jpg';

        if (! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('The product cover could not be stored.');
        }

        return $path;
    }
}
