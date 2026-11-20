<?php

use App\Enums\ProductKeyStatus;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

/**
 * A real upload of PNG bytes named like a CSV. Fake uploads report the MIME
 * type of their extension, so they cannot test the `mimetypes` rule.
 */
function binaryUpload(string $name): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'keys');
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

    return new UploadedFile($path, $name, null, null, true);
}

it('lists the keys of the product by status and date, never their codes', function () {
    $product = Product::factory()->create();
    ProductKey::factory()->for($product)->create(['code' => 'SECRET-00001']);
    ProductKey::factory()->for($product)->sold()->create(['code' => 'SECRET-00002']);
    ProductKey::factory()->create(['code' => 'OTHER-00001']);

    $this->get(route('products.keys.index', $product))
        ->assertOk()
        ->assertDontSee('SECRET-0000')
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/products/keys/Index')
            ->where('product.id', $product->id)
            ->where('product.statusCounts', [
                ['label' => 'Disponible', 'total' => 1],
                ['label' => 'Reservada', 'total' => 0],
                ['label' => 'Vendida', 'total' => 1],
            ])
            ->where('keys.meta.total', 2)
            ->has('keys.data.0', fn (Assert $row) => $row
                ->hasAll(['id', 'status', 'created_at'])
                ->missingAll(['code', 'code_hash'])
                ->etc()));
});

it('has no keys page for a physical product', function () {
    $product = Product::factory()->physical()->create();

    $this->get(route('products.keys.index', $product))->assertNotFound();
});

it('imports the keys of an uploaded file and flashes the result', function () {
    $product = Product::factory()->create();
    ProductKey::factory()->create(['code' => 'TAKEN-00001']);
    $file = UploadedFile::fake()->createWithContent('keys.csv', "ABCDE-12345\nTAKEN-00001\nBAD\nABCDE-12345\nFGHIJ-67890\n");

    $this->post(route('products.keys.store', $product), ['file' => $file])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.keys.index', $product))
        ->assertInertiaFlash('keyImport', [
            'imported' => 2,
            'rejectedCount' => 3,
            'rejections' => [
                ['line' => 2, 'reason' => 'Ya existe en la base.'],
                ['line' => 3, 'reason' => 'Formato inválido: de 5 a 100 caracteres entre letras, números y guiones.'],
                ['line' => 4, 'reason' => 'Repetido en el archivo.'],
            ],
        ])
        ->assertInertiaFlash('toast.message', '2 keys importadas, 3 rechazadas.');

    expect($product->keys()->where('status', ProductKeyStatus::Available)->count())->toBe(2);
});

it('sends back only the first 100 rejected lines', function () {
    $product = Product::factory()->create();
    $file = UploadedFile::fake()->createWithContent('keys.txt', str_repeat("BAD\n", 150));

    $this->post(route('products.keys.store', $product), ['file' => $file])
        ->assertInertiaFlash('keyImport.rejectedCount', 150)
        ->assertInertiaFlash('keyImport.rejections.99.line', 100)
        ->assertInertiaFlashMissing('keyImport.rejections.100');
});

it('validates the uploaded file', function (Closure $file, string $message) {
    $product = Product::factory()->create();

    $this->post(route('products.keys.store', $product), ['file' => $file()])
        ->assertSessionHasErrors(['file' => $message]);

    $this->assertDatabaseCount('product_keys', 0);
})->with([
    'missing' => [fn () => null, 'El campo archivo es obligatorio.'],
    'wrong extension' => [fn () => UploadedFile::fake()->createWithContent('keys.xlsx', 'ABCDE-12345'), 'El archivo debe tener una de estas extensiones: csv, txt.'],
    'binary' => [fn () => binaryUpload('keys.csv'), 'El archivo debe ser un archivo de texto (text/plain, text/csv, application/csv).'],
    'over 1 MB' => [fn () => UploadedFile::fake()->createWithContent('keys.csv', str_repeat("ABCDE-12345\n", 90_000)), 'El archivo no puede pesar más de 1024 kilobytes.'],
    'without codes' => [fn () => UploadedFile::fake()->createWithContent('keys.csv', "\n\n"), 'El archivo no tiene códigos.'],
]);

it('refuses to import into a physical product', function () {
    $product = Product::factory()->physical()->create();
    $file = UploadedFile::fake()->createWithContent('keys.csv', 'ABCDE-12345');

    $this->post(route('products.keys.store', $product), ['file' => $file])
        ->assertSessionHasErrors(['product' => 'Solo los productos de tipo key digital o licencia tienen keys.']);

    $this->assertDatabaseCount('product_keys', 0);
});
