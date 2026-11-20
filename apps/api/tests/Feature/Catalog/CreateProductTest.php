<?php

use App\Actions\Catalog\CreateProduct;
use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Product;
use App\Models\Region;
use App\Models\Title;

function createProduct(ProductType $type, mixed ...$overrides): Product
{
    return app(CreateProduct::class)->handle($type, productData(...$overrides));
}

it('creates a key product with its platforms and a generated slug', function () {
    $title = Title::factory()->create(['name' => 'Starfall']);
    $steam = ActivationService::factory()->create(['name' => 'Steam', 'slug' => 'steam']);
    $global = Region::factory()->create(['name' => 'Global', 'slug' => 'global']);
    $windows = Platform::factory()->create(['name' => 'Windows', 'slug' => 'windows']);
    $macos = Platform::factory()->create(['name' => 'macOS', 'slug' => 'macos']);

    $product = createProduct(
        ProductType::DigitalKey,
        titleId: $title->id,
        activationServiceId: $steam->id,
        regionId: $global->id,
        platformIds: [$windows->id, $macos->id],
        price: 19990,
        isPublished: true,
    );
    checkDeferredConstraints();

    expect($product->refresh())
        ->type->toBe(ProductType::DigitalKey)
        ->slug->toBe('starfall-steam-macos-windows-global')
        ->price->toBe(19990)
        ->stock->toBeNull()
        ->is_published->toBeTrue()
        ->and($product->platforms->pluck('id')->sort()->values()->all())
        ->toBe(collect([$windows->id, $macos->id])->sort()->values()->all());
});

it('creates a physical product with stock and no activation service', function () {
    $product = createProduct(ProductType::Physical, activationServiceId: null, stock: 5);
    checkDeferredConstraints();

    expect($product->refresh())
        ->type->toBe(ProductType::Physical)
        ->activation_service_id->toBeNull()
        ->stock->toBe(5)
        ->slug->toContain('-physical-');
});

it('keeps a slug typed by the admin', function () {
    $product = createProduct(ProductType::DigitalKey, slug: 'starfall-pc');

    expect($product->slug)->toBe('starfall-pc');
});

it('suffixes a generated slug that is already taken', function () {
    $title = Title::factory()->create(['name' => 'Starfall']);
    $steam = ActivationService::factory()->create(['slug' => 'steam']);
    $global = Region::factory()->create(['slug' => 'global']);
    $windows = Platform::factory()->create(['slug' => 'windows']);
    Product::factory()->create(['slug' => 'starfall-steam-windows-global']);

    $product = createProduct(
        ProductType::DigitalKey,
        titleId: $title->id,
        activationServiceId: $steam->id,
        regionId: $global->id,
        platformIds: [$windows->id],
    );

    expect($product->slug)->toBe('starfall-steam-windows-global-2');
});

it('rejects invalid product data with a readable error', function (ProductType $type, array $overrides, string $field, string $message) {
    $errors = validationErrorsOf(fn () => createProduct($type, ...$overrides));

    expect($errors)->toHaveKey($field)
        ->and($errors[$field])->toContain($message)
        ->and(Product::query()->count())->toBe(0);
})->with([
    'key without activation service' => [ProductType::DigitalKey, ['activationServiceId' => null], 'activation_service_id', 'El campo servicio de activación es obligatorio.'],
    'license without region' => [ProductType::License, ['regionId' => null], 'region_id', 'El campo región es obligatorio.'],
    'key with stock' => [ProductType::DigitalKey, ['stock' => 5], 'stock', 'Para este tipo, el stock se cuenta a partir de las claves disponibles.'],
    'physical without stock' => [ProductType::Physical, ['stock' => null], 'stock', 'El campo stock es obligatorio.'],
    'physical with negative stock' => [ProductType::Physical, ['stock' => -1], 'stock', 'El campo stock debe ser al menos 0.'],
    'no platforms' => [ProductType::DigitalKey, ['platformIds' => []], 'platform_ids', 'El campo plataformas es obligatorio.'],
    'zero price' => [ProductType::DigitalKey, ['price' => 0], 'price', 'El campo precio debe ser al menos 1.'],
    'unknown title' => [ProductType::DigitalKey, ['titleId' => 999999], 'title_id', 'El título seleccionado no existe.'],
    'unknown platform' => [ProductType::DigitalKey, ['platformIds' => [999999]], 'platform_ids.0', 'El plataforma seleccionado no existe.'],
    'uppercase slug' => [ProductType::DigitalKey, ['slug' => 'Starfall-PC'], 'slug', 'Usa minúsculas, números y guiones simples.'],
]);

it('rejects a physical product with two platforms', function () {
    $platforms = Platform::factory()->count(2)->create()->modelKeys();

    $errors = validationErrorsOf(fn () => createProduct(ProductType::Physical, stock: 1, platformIds: $platforms));

    expect($errors['platform_ids'])->toBe(['Un producto físico cubre exactamente una plataforma.']);
});

it('rejects a slug typed by the admin that is already taken', function () {
    Product::factory()->create(['slug' => 'starfall-pc']);

    $errors = validationErrorsOf(fn () => createProduct(ProductType::DigitalKey, slug: 'starfall-pc'));

    expect($errors['slug'])->toBe(['El slug ya está en uso.']);
});

it('rejects a product with the identity of an existing one', function () {
    $existing = createProduct(ProductType::DigitalKey, slug: 'starfall-ps5-latam');
    $platformIds = $existing->platforms()->pluck('platforms.id')->all();

    $errors = validationErrorsOf(fn () => createProduct(
        ProductType::DigitalKey,
        titleId: $existing->title_id,
        activationServiceId: $existing->activation_service_id,
        regionId: $existing->region_id,
        platformIds: $platformIds,
    ));

    expect($errors['product'])->toBe(['Este producto ya existe: starfall-ps5-latam.'])
        ->and(Product::query()->count())->toBe(1);
});

it('accepts a product whose platforms are a superset of an existing one', function () {
    $existing = createProduct(ProductType::DigitalKey);
    $platformIds = [...$existing->platforms()->pluck('platforms.id')->all(), Platform::factory()->create()->id];

    createProduct(
        ProductType::DigitalKey,
        titleId: $existing->title_id,
        activationServiceId: $existing->activation_service_id,
        regionId: $existing->region_id,
        platformIds: $platformIds,
    );
    checkDeferredConstraints();

    expect(Product::query()->count())->toBe(2);
});

it('accepts the same identity under a different type', function () {
    $existing = createProduct(ProductType::DigitalKey);

    createProduct(
        ProductType::Physical,
        titleId: $existing->title_id,
        activationServiceId: $existing->activation_service_id,
        regionId: $existing->region_id,
        platformIds: $existing->platforms()->pluck('platforms.id')->all(),
        stock: 3,
    );
    checkDeferredConstraints();

    expect(Product::query()->count())->toBe(2);
});
