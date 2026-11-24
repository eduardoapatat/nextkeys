<?php

use App\Enums\ChileRegion;

it('lists the 16 regions and the 346 communes of Chile', function () {
    $communes = array_merge(...array_map(fn (ChileRegion $region): array => $region->communes(), ChileRegion::cases()));

    expect(ChileRegion::cases())->toHaveCount(16)
        ->and($communes)->toHaveCount(346);
});

it('lists each commune once within its region', function (ChileRegion $region) {
    expect($region->communes())->toBe(array_values(array_unique($region->communes())));
})->with(ChileRegion::cases());

it('knows which communes belong to a region', function () {
    expect(ChileRegion::Metropolitana->hasCommune('Ñuñoa'))->toBeTrue()
        ->and(ChileRegion::Valparaiso->hasCommune('Ñuñoa'))->toBeFalse();
});
