<?php

namespace App\Actions\Catalog;

use App\Enums\KeyImportRejectionReason;

/**
 * A line of a key import file that was not imported. It never carries the
 * code: codes are never shown back, only where they are in the file.
 */
final readonly class KeyImportRejection
{
    public function __construct(
        public int $line,
        public KeyImportRejectionReason $reason,
    ) {}
}
