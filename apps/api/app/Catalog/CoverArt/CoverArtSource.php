<?php

namespace App\Catalog\CoverArt;

/**
 * Where the demo catalog gets official cover art for a title. The
 * implementation talks to the provider; the rest of the app only sees this.
 */
interface CoverArtSource
{
    /**
     * Whether the source has what it needs to answer (e.g. credentials).
     */
    public function isConfigured(): bool;

    /**
     * The cover image (JPEG bytes) of the game with this exact name, or null
     * when the source does not know it.
     *
     * @throws CoverArtUnavailable When the source cannot be reached or rejects the request.
     */
    public function coverFor(string $title): ?string;
}
