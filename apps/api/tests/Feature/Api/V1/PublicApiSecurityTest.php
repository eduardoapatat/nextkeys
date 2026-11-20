<?php

use App\Models\Product;
use App\Models\ProductKey;

it('never exposes key codes, hints, stock or internal fields', function () {
    $product = Product::factory()->published()->create();
    $key = ProductKey::factory()->for($product)->create(['code' => 'SECRET-ABCDE-7QF2Z']);
    $physical = Product::factory()->physical()->published()->create(['stock' => 7]);

    foreach ([
        route('api.v1.products.index'),
        route('api.v1.products.show', $product->slug),
        route('api.v1.products.show', $physical->slug),
    ] as $url) {
        $response = $this->getJson($url)->assertOk();

        $response->assertDontSee('SECRET')
            ->assertDontSee($key->code_hint)
            ->assertDontSee('"stock"', escape: false)
            ->assertDontSee('"code', escape: false)
            ->assertDontSee('"status"', escape: false)
            ->assertDontSee('"id"', escape: false)
            ->assertDontSee('_at"', escape: false);
    }
});

it('limits each IP to 120 requests per minute', function () {
    $this->getJson(route('api.v1.catalog.filters'))
        ->assertOk()
        ->assertHeader('X-RateLimit-Limit', '120');
});

it('allows cross-origin reads only from the storefront', function () {
    $this->getJson(route('api.v1.catalog.filters'), ['Origin' => 'http://storefront.test'])
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', 'http://storefront.test');

    $otherOrigin = $this->getJson(route('api.v1.catalog.filters'), ['Origin' => 'https://evil.test'])->assertOk();

    expect($otherOrigin->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://evil.test');

    $this->call('OPTIONS', route('api.v1.catalog.filters'), server: [
        'HTTP_ORIGIN' => 'http://storefront.test',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
    ])->assertHeader('Access-Control-Allow-Methods', 'GET');
});
