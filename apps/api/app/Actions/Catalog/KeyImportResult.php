<?php

namespace App\Actions\Catalog;

/**
 * The outcome of a key import: how many codes were stored and which lines
 * were rejected, ordered by line.
 */
final readonly class KeyImportResult
{
    /**
     * @param  list<KeyImportRejection>  $rejections
     */
    public function __construct(
        public int $imported,
        public array $rejections,
    ) {}
}
