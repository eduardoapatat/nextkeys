<?php

namespace App\Catalog\CoverArt\Igdb;

use App\Catalog\CoverArt\CoverArtSource;
use App\Catalog\CoverArt\CoverArtUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Official cover art from IGDB (Twitch). Authenticates with the app's
 * client credentials, searches the game by name and downloads its cover at
 * 1080p (about 810 × 1080). IGDB lists games only: programs return null.
 */
final class IgdbCoverArt implements CoverArtSource
{
    private const string TOKEN_URL = 'https://id.twitch.tv/oauth2/token';

    private const string GAMES_URL = 'https://api.igdb.com/v4/games';

    private const string IMAGE_URL = 'https://images.igdb.com/igdb/image/upload/t_1080p/%s.jpg';

    private const string TOKEN_CACHE_KEY = 'igdb.access_token';

    public function __construct(
        private readonly ?string $clientId,
        private readonly ?string $clientSecret,
        private readonly int $timeoutSeconds = 15,
    ) {}

    public function isConfigured(): bool
    {
        return $this->clientId !== null && $this->clientId !== ''
            && $this->clientSecret !== null && $this->clientSecret !== '';
    }

    public function coverFor(string $title): ?string
    {
        if (! $this->isConfigured()) {
            throw new CoverArtUnavailable('IGDB credentials are not set (IGDB_CLIENT_ID, IGDB_CLIENT_SECRET).');
        }

        $imageId = $this->coverImageId($title);

        if ($imageId === null) {
            return null;
        }

        $image = $this->send(fn () => Http::timeout($this->timeoutSeconds)->get(sprintf(self::IMAGE_URL, $imageId)));

        return $image->body();
    }

    /**
     * The cover image id of the game named exactly $title (ignoring case),
     * or of the best search match when no name is exact.
     */
    private function coverImageId(string $title): ?string
    {
        $query = sprintf('search "%s"; fields name,cover.image_id; where cover != null; limit 10;', addcslashes($title, '"\\'));

        $response = $this->send(fn () => Http::timeout($this->timeoutSeconds)
            ->withHeaders(['Client-ID' => (string) $this->clientId])
            ->withToken($this->accessToken())
            ->withBody($query, 'text/plain')
            ->post(self::GAMES_URL));

        /** @var list<array{name?: string, cover?: array{image_id?: string}}> $games */
        $games = array_values(array_filter((array) $response->json(), is_array(...)));

        $exact = array_values(array_filter($games, fn (array $game): bool => mb_strtolower($game['name'] ?? '') === mb_strtolower($title)));
        $game = $exact[0] ?? $games[0] ?? null;
        $imageId = $game['cover']['image_id'] ?? null;

        return is_string($imageId) && preg_match('/^[a-z0-9]+$/', $imageId) === 1 ? $imageId : null;
    }

    private function accessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if (is_string($cached)) {
            return $cached;
        }

        $response = $this->send(fn () => Http::timeout($this->timeoutSeconds)->asForm()->post(self::TOKEN_URL, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]));

        $token = $response->json('access_token');
        $expiresIn = $response->json('expires_in');

        if (! is_string($token) || ! is_int($expiresIn)) {
            throw new CoverArtUnavailable('IGDB did not return an access token.');
        }

        Cache::put(self::TOKEN_CACHE_KEY, $token, max(60, $expiresIn - 300));

        return $token;
    }

    /**
     * @param  callable(): Response  $request
     */
    private function send(callable $request): Response
    {
        try {
            $response = $request();
        } catch (ConnectionException) {
            throw new CoverArtUnavailable('IGDB could not be reached.');
        }

        if ($response->failed()) {
            throw new CoverArtUnavailable("IGDB answered with HTTP {$response->status()}.");
        }

        return $response;
    }
}
