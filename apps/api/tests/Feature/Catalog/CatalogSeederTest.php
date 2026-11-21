<?php

use App\Enums\ProductStatus;
use App\Models\Product;

it('seeds a demo catalog that satisfies every database rule', function () {
    $this->seed();

    checkDeferredConstraints();

    expect(Product::query()->count())->toBe(41)
        ->and(Product::query()->where('status', ProductStatus::Draft)->count())->toBe(1);
});
