<?php

use App\Models\Product;
use App\Models\Title;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    config(['services.igdb.client_id' => 'client-id-123', 'services.igdb.client_secret' => 'client-secret-456']);
});

/**
 * Fakes Twitch and IGDB: every search finds the game named in it, except
 * the names in $unknown.
 *
 * @param  list<string>  $unknown
 */
function fakeIgdb(array $unknown = []): void
{
    Http::fake([
        'id.twitch.tv/oauth2/token' => Http::response(['access_token' => 'token-789', 'expires_in' => 5000]),
        'api.igdb.com/v4/games' => function (Request $request) use ($unknown) {
            preg_match('/search "([^"]+)"/', $request->body(), $match);
            $name = $match[1] ?? '';

            return Http::response(in_array($name, $unknown, true) ? [] : [
                ['id' => 1, 'name' => "{$name}: Deluxe", 'cover' => ['image_id' => 'cowrong'], 'total_rating_count' => 900],
                ['id' => 2, 'name' => $name, 'cover' => ['image_id' => 'cowrong'], 'total_rating_count' => 3],
                ['id' => 3, 'name' => $name, 'cover' => ['image_id' => 'co'.strtolower(substr(md5($name), 0, 6))], 'total_rating_count' => 400],
            ]);
        },
        'images.igdb.com/*' => Http::response('jpeg-bytes'),
    ]);
}

function productOf(string $title, array $attributes = []): Product
{
    return Product::factory()
        ->for(Title::factory()->state(['name' => $title]))
        ->create($attributes);
}

it('gives every product without a cover the cover of its title (exact name, most rated), one search per title', function () {
    fakeIgdb();
    $hades = productOf('Hades', ['cover_band' => false]);
    $other = Product::factory()->for($hades->title)->create(['slug' => 'hades-other']);
    $covered = productOf('Elden Ring', ['cover_image' => 'covers/mine.jpg']);
    Storage::disk('public')->put('covers/mine.jpg', 'mine');

    $this->artisan('catalog:import-covers')
        ->expectsOutput('Hades: cover imported.')
        ->assertSuccessful();

    foreach ([$hades->refresh(), $other->refresh()] as $product) {
        expect($product->cover_band)->toBeTrue();
        Storage::disk('public')->assertExists((string) $product->cover_image);
        expect(Storage::disk('public')->get((string) $product->cover_image))->toBe('jpeg-bytes');
    }
    expect($hades->cover_image)->not->toBe($other->cover_image)
        ->and($covered->refresh()->cover_image)->toBe('covers/mine.jpg');

    Http::assertSentCount(3);
    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'images.igdb.com') && ! str_contains($request->url(), 'cowrong'));
    Http::assertSent(fn (Request $request) => $request->hasHeader('Client-ID', 'client-id-123') || ! str_contains($request->url(), 'api.igdb.com'));
});

it('replaces existing covers with --force and deletes the old files', function () {
    fakeIgdb();
    Storage::disk('public')->put('covers/old.jpg', 'old');
    $product = productOf('Hades', ['cover_image' => 'covers/old.jpg']);

    $this->artisan('catalog:import-covers --force')->assertSuccessful();

    expect($product->refresh()->cover_image)->not->toBe('covers/old.jpg');
    Storage::disk('public')->assertMissing('covers/old.jpg');
    expect(Storage::disk('public')->allFiles('covers'))->toHaveCount(1);
});

it('only touches the title asked for', function () {
    fakeIgdb();
    productOf('Hades');
    $elden = productOf('Elden Ring');

    $this->artisan('catalog:import-covers', ['--title' => 'Hades'])->assertSuccessful();

    expect($elden->refresh()->cover_image)->toBeNull();
});

it('leaves a title IGDB does not know without a cover and says so', function () {
    fakeIgdb(unknown: ['Norton 360 Deluxe']);
    $product = productOf('Norton 360 Deluxe');

    $this->artisan('catalog:import-covers')
        ->expectsOutput('Norton 360 Deluxe: not found on IGDB; upload its cover from the panel.')
        ->assertSuccessful();

    expect($product->refresh()->cover_image)->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('only warns when IGDB is not configured', function () {
    config(['services.igdb.client_id' => null]);
    Http::fake();
    productOf('Hades');

    $this->artisan('catalog:import-covers')
        ->expectsOutputToContain('IGDB is not configured')
        ->assertSuccessful();

    Http::assertNothingSent();
});

it('fails without leaking the credentials or the token when IGDB refuses', function () {
    Http::fake([
        'id.twitch.tv/oauth2/token' => Http::response(['access_token' => 'token-789', 'expires_in' => 5000]),
        'api.igdb.com/v4/games' => Http::response(['message' => 'token-789 client-secret-456'], 401),
    ]);
    $product = productOf('Hades');

    $this->artisan('catalog:import-covers')
        ->expectsOutput('IGDB answered with HTTP 401.')
        ->doesntExpectOutputToContain('token-789')
        ->doesntExpectOutputToContain('client-secret-456')
        ->assertFailed();

    expect($product->refresh()->cover_image)->toBeNull();
});
