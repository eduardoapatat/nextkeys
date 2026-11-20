<?php

use App\Models\Product;

it('seeds a demo catalog that satisfies every database rule', function () {
    $this->seed();

    checkDeferredConstraints();

    expect(Product::query()->count())->toBe(11)
        ->and(Product::query()->where('is_published', false)->count())->toBe(1);
});
