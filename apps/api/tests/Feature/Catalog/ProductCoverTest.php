<?php

use App\Actions\Catalog\CreateProduct;
use App\Actions\Catalog\DeleteProduct;
use App\Actions\Catalog\UpdateProduct;
use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function cover(string $name = 'cover.jpg', int $width = 600, int $height = 800): UploadedFile
{
    return UploadedFile::fake()->image($name, $width, $height);
}

/**
 * The product's current data with some fields changed.
 */
function coverDataOf(Product $product, mixed ...$overrides): mixed
{
    return productData(...[
        'titleId' => $product->title_id,
        'activationServiceId' => $product->activation_service_id,
        'regionId' => $product->region_id,
        'platformIds' => $product->platforms()->pluck('platforms.id')->all(),
        'price' => $product->price,
        'stock' => $product->stock,
        'isPublished' => $product->is_published,
        ...$overrides,
    ]);
}

it('stores the cover of a new product on the public disk', function () {
    $product = app(CreateProduct::class)->handle(ProductType::DigitalKey, productData(cover: cover()));
    checkDeferredConstraints();

    expect($product->cover_image)->toStartWith('covers/')
        ->and($product->coverUrl())->toEndWith('/storage/'.$product->cover_image);
    Storage::disk('public')->assertExists((string) $product->cover_image);
});

it('replaces the cover and deletes the previous file', function () {
    $product = app(CreateProduct::class)->handle(ProductType::DigitalKey, productData(cover: cover()));
    $previous = (string) $product->cover_image;

    app(UpdateProduct::class)->handle($product, coverDataOf($product, cover: cover('new.png')));

    expect($product->refresh()->cover_image)->not->toBe($previous)->toEndWith('.png');
    Storage::disk('public')->assertMissing($previous);
    Storage::disk('public')->assertExists((string) $product->cover_image);
});

it('keeps the cover when the update brings none', function () {
    $product = app(CreateProduct::class)->handle(ProductType::DigitalKey, productData(cover: cover()));
    $previous = $product->cover_image;

    app(UpdateProduct::class)->handle($product, coverDataOf($product, price: 9990));

    expect($product->refresh()->cover_image)->toBe($previous);
    Storage::disk('public')->assertExists((string) $previous);
});

it('removes the cover and its file', function () {
    $product = app(CreateProduct::class)->handle(ProductType::DigitalKey, productData(cover: cover()));
    $previous = (string) $product->cover_image;

    app(UpdateProduct::class)->handle($product, coverDataOf($product, removeCover: true));

    expect($product->refresh()->cover_image)->toBeNull()
        ->and($product->coverUrl())->toBeNull();
    Storage::disk('public')->assertMissing($previous);
});

it('deletes the new file and keeps the old one when the save fails', function () {
    $product = app(CreateProduct::class)->handle(ProductType::DigitalKey, productData(cover: cover()));
    $previous = (string) $product->cover_image;
    $taken = Product::factory()->create(['slug' => 'taken-slug']);

    validationErrorsOf(fn () => app(UpdateProduct::class)->handle($product, coverDataOf($product, slug: 'taken-slug', cover: cover('new.png'))));

    expect(Storage::disk('public')->allFiles('covers'))->toBe([$previous])
        ->and($product->refresh()->cover_image)->toBe($previous)
        ->and($taken->exists)->toBeTrue();
});

it('rejects a cover that is not a valid image', function (Closure $file, string $message) {
    $errors = validationErrorsOf(fn () => app(CreateProduct::class)->handle(ProductType::DigitalKey, productData(cover: $file())));

    expect($errors['cover'])->toBe([$message])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
})->with([
    'pdf' => [fn () => UploadedFile::fake()->create('cover.pdf', 100, 'application/pdf'), 'El campo carátula debe ser una imagen.'],
    'gif' => [fn () => UploadedFile::fake()->image('cover.gif', 600, 800), 'El campo carátula debe ser un archivo de tipo: jpg, jpeg, png, webp.'],
    'too small' => [fn () => cover(width: 299, height: 800), 'El campo carátula debe medir al menos 300 × 400 píxeles.'],
    'over 2 MB' => [fn () => UploadedFile::fake()->image('cover.jpg', 600, 800)->size(2049), 'La carátula no puede pesar más de 2 MB.'],
]);

it('deletes the cover with the product', function () {
    $product = app(CreateProduct::class)->handle(ProductType::DigitalKey, productData(cover: cover()));
    $path = (string) $product->cover_image;

    app(DeleteProduct::class)->handle($product);

    Storage::disk('public')->assertMissing($path);
});
