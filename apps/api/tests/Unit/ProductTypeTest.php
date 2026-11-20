<?php

use App\Enums\ProductType;

test('only digital keys and licenses are delivered by key', function (ProductType $type, bool $deliveredByKey) {
    expect($type->isDeliveredByKey())->toBe($deliveredByKey);
})->with([
    'digital key' => [ProductType::DigitalKey, true],
    'license' => [ProductType::License, true],
    'physical' => [ProductType::Physical, false],
]);
