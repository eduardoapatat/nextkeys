<?php

use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\Region;
use App\Models\Title;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('lists products with their stock and no key codes', function () {
    $title = Title::factory()->create(['name' => 'Starfall']);
    $windows = Platform::factory()->create(['name' => 'Windows']);
    $product = Product::factory()->for($title)->withPlatforms($windows)->published()->create(['price' => 19990]);
    ProductKey::factory()->for($product)->count(2)->create();
    Product::factory()->physical()->create(['stock' => 4, 'price' => 29990]);

    $this->get(route('products.index', ['sort' => 'price']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/products/Index')
            ->where('products.meta.total', 2)
            ->where('products.data.0.id', $product->id)
            ->where('products.data.0.title', 'Starfall')
            ->where('products.data.0.platforms', ['Windows'])
            ->where('products.data.0.activation_service', $product->activationService?->name)
            ->where('products.data.0.available_stock', 2)
            ->where('products.data.0.status', 'published')
            ->where('products.data.1.available_stock', 4)
            ->missing('products.data.0.keys'));
});

it('creates a product from the form', function () {
    $title = Title::factory()->create(['name' => 'Starfall']);
    $steam = ActivationService::factory()->create(['slug' => 'steam']);
    $global = Region::factory()->create(['slug' => 'global']);
    $windows = Platform::factory()->create(['slug' => 'windows']);

    $this->post(route('products.store'), [
        'type' => 'digital_key',
        'title_id' => $title->id,
        'activation_service_id' => $steam->id,
        'region_id' => $global->id,
        'platform_ids' => [$windows->id],
        'price' => 19990,
        'stock' => '',
        'status' => 'published',
        'slug' => '',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.index'))
        ->assertInertiaFlash('toast.message', 'Producto starfall-steam-windows-global creado.');

    $this->assertDatabaseHas('products', [
        'slug' => 'starfall-steam-windows-global',
        'type' => 'digital_key',
        'status' => 'published',
    ]);
});

it('requires the type and the shape of the fields', function () {
    $this->post(route('products.store'), ['platform_ids' => ['x'], 'price' => 'free'])
        ->assertSessionHasErrors([
            'type' => 'El campo tipo es obligatorio.',
            'title_id' => 'El campo título es obligatorio.',
            'status' => 'El campo estado es obligatorio.',
            'price' => 'El campo precio debe ser un número entero.',
            'platform_ids.0' => 'El campo plataforma debe ser un número entero.',
        ]);

    expect(Product::query()->count())->toBe(0);
});

it('rejects an unknown status', function () {
    $this->post(route('products.store'), ['status' => 'deleted'])
        ->assertSessionHasErrors(['status' => 'El estado seleccionado no es válido.']);
});

it('filters the list by status', function () {
    Product::factory()->published()->create();
    $archived = Product::factory()->archived()->create();

    $this->get(route('products.index', ['filter' => ['status' => 'archived']]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('products.meta.total', 1)
            ->where('products.data.0.id', $archived->id)
            ->where('products.data.0.status', 'archived'));
});

it('returns the catalog rule errors of the Action', function () {
    $this->post(route('products.store'), [
        'type' => 'license',
        'title_id' => Title::factory()->create()->id,
        'platform_ids' => [Platform::factory()->create()->id],
        'price' => 9990,
        'status' => 'draft',
    ])->assertSessionHasErrors([
        'activation_service_id' => 'El campo servicio de activación es obligatorio.',
        'region_id' => 'El campo región es obligatorio.',
    ]);
});

it('shows the edit form with the product and the form options', function () {
    $product = Product::factory()->physical()->create();

    $this->get(route('products.edit', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/products/Edit')
            ->where('product.id', $product->id)
            ->where('product.type', 'physical')
            ->where('product.status', 'draft')
            ->where('types.2', ['value' => 'physical', 'label' => 'Físico'])
            ->where('statuses.2', ['value' => 'archived', 'label' => 'Archivado'])
            ->has('titles', 1)
            ->has('platforms', 1));
});

it('ignores a type sent when updating a product', function () {
    $product = Product::factory()->create(['slug' => 'starfall-pc']);

    $this->put(route('products.update', $product), [
        'type' => 'physical',
        'title_id' => $product->title_id,
        'activation_service_id' => $product->activation_service_id,
        'region_id' => $product->region_id,
        'platform_ids' => $product->platforms()->pluck('platforms.id')->all(),
        'price' => 29990,
        'status' => 'draft',
        'slug' => '',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.index'));

    expect($product->refresh())
        ->type->toBe(ProductType::DigitalKey)
        ->price->toBe(29990)
        ->slug->toBe('starfall-pc');
});

it('uploads a cover through a spoofed PUT and shows it in the edit form', function () {
    Storage::fake('public');
    $product = Product::factory()->create();

    $this->post(route('products.update', $product), [
        '_method' => 'put',
        'title_id' => $product->title_id,
        'activation_service_id' => $product->activation_service_id,
        'region_id' => $product->region_id,
        'platform_ids' => $product->platforms()->pluck('platforms.id')->all(),
        'price' => $product->price,
        'status' => 'draft',
        'cover' => UploadedFile::fake()->image('cover.webp', 600, 800),
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.index'));

    $path = (string) $product->refresh()->cover_image;
    Storage::disk('public')->assertExists($path);

    $this->get(route('products.edit', $product))
        ->assertInertia(fn (Assert $page) => $page->where('product.cover_url', Storage::disk('public')->url($path)));
});

it('deletes a product without keys', function () {
    $product = Product::factory()->create();

    $this->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'))
        ->assertInertiaFlash('toast.message', 'Producto eliminado.');

    $this->assertModelMissing($product);
});

it('refuses to delete a product with keys', function () {
    $product = Product::factory()->create();
    ProductKey::factory()->for($product)->create();

    $this->from(route('products.index'))
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'))
        ->assertSessionHasErrors(['product' => 'Este producto tiene claves y no se puede eliminar. Archívalo en su lugar.']);

    $this->assertModelExists($product);
});

it('forbids the product pages to customers', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('products.index'))
        ->assertForbidden();
});
