<?php

use App\Actions\Catalog\ImportProductKeys;
use App\Actions\Catalog\KeyImportRejection;
use App\Actions\Catalog\KeyImportResult;
use App\Enums\KeyImportRejectionReason;
use App\Enums\ProductKeyStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductKey;

function importKeys(Product $product, string $contents): KeyImportResult
{
    return app(ImportProductKeys::class)->handle($product, $contents);
}

/**
 * @return list<array{int, KeyImportRejectionReason}>
 */
function rejectionsOf(KeyImportResult $result): array
{
    return array_map(
        fn (KeyImportRejection $rejection): array => [$rejection->line, $rejection->reason],
        $result->rejections,
    );
}

it('imports the codes as available keys, trimmed and keeping their case', function () {
    $product = Product::factory()->license()->create();

    $result = importKeys($product, "  abCDE-12345 \nFGHIJ-67890\n");

    expect($result->imported)->toBe(2)
        ->and($result->rejections)->toBe([]);

    $keys = $product->keys()->orderBy('id')->get();
    expect($keys->map->code->all())->toBe(['abCDE-12345', 'FGHIJ-67890'])
        ->and($keys[0]->code_hash)->toBe(ProductKey::hashCode('ABCDE-12345'))
        ->and($keys[0]->product_type)->toBe(ProductType::License)
        ->and($keys[0]->status)->toBe(ProductKeyStatus::Available);
});

it('skips blank lines, a code header, a BOM and CRLF line endings', function () {
    $product = Product::factory()->create();

    $result = importKeys($product, "\xEF\xBB\xBFCode\r\n\r\nABCDE-12345\r\n   \r\nFGHIJ-67890");

    expect($result->imported)->toBe(2)
        ->and($result->rejections)->toBe([])
        ->and($product->keys()->count())->toBe(2);
});

it('rejects lines with an invalid format, by line number', function () {
    $product = Product::factory()->create();
    $tooLong = str_repeat('A', 101);

    $result = importKeys($product, "ABCD\nABCDE-12345\nABC DE-123\nABCDE,12345\nABCDE_12345\n{$tooLong}\n".str_repeat('B', 100));

    expect($result->imported)->toBe(2)
        ->and(rejectionsOf($result))->toBe([
            [1, KeyImportRejectionReason::InvalidFormat],
            [3, KeyImportRejectionReason::InvalidFormat],
            [4, KeyImportRejectionReason::InvalidFormat],
            [5, KeyImportRejectionReason::InvalidFormat],
            [6, KeyImportRejectionReason::InvalidFormat],
        ]);
});

it('keeps the first of the codes repeated in the file, ignoring case', function () {
    $product = Product::factory()->create();

    $result = importKeys($product, "abcde-12345\nFGHIJ-67890\nABCDE-12345\nabcde-12345");

    expect($result->imported)->toBe(2)
        ->and(rejectionsOf($result))->toBe([
            [3, KeyImportRejectionReason::DuplicateInFile],
            [4, KeyImportRejectionReason::DuplicateInFile],
        ])
        ->and($product->keys()->get()->map->code->all())->toContain('abcde-12345');
});

it('rejects codes that already exist in this or another product', function () {
    $product = Product::factory()->create();
    $other = Product::factory()->create();
    ProductKey::factory()->for($product)->create(['code' => 'ABCDE-12345']);
    $taken = ProductKey::factory()->for($other)->sold()->create(['code' => 'FGHIJ-67890']);

    $result = importKeys($product, "abcde-12345\nNEW00-00001\nfghij-67890");

    expect($result->imported)->toBe(1)
        ->and(rejectionsOf($result))->toBe([
            [1, KeyImportRejectionReason::AlreadyExists],
            [3, KeyImportRejectionReason::AlreadyExists],
        ])
        ->and($product->keys()->count())->toBe(2)
        ->and($taken->fresh()?->product_id)->toBe($other->id)
        ->and($taken->fresh()?->status)->toBe(ProductKeyStatus::Sold);
});

it('refuses to import keys into a physical product', function () {
    $product = Product::factory()->physical()->create();

    $errors = validationErrorsOf(fn () => importKeys($product, 'ABCDE-12345'));

    expect($errors['product'])->toBe(['Solo los productos de tipo key digital o licencia tienen keys.']);
    $this->assertDatabaseCount('product_keys', 0);
});

it('refuses a file without codes', function () {
    $product = Product::factory()->create();

    $errors = validationErrorsOf(fn () => importKeys($product, "code\n\n  \n"));

    expect($errors['file'])->toBe(['El archivo no tiene códigos.']);
});

it('refuses a file with more codes than the limit and imports none', function () {
    $product = Product::factory()->create();
    $codes = implode("\n", array_map(fn (int $n): string => sprintf('CODE-%05d', $n), range(1, ImportProductKeys::MAX_CODES + 1)));

    $errors = validationErrorsOf(fn () => importKeys($product, $codes));

    expect($errors['file'])->toBe(['El archivo tiene 5001 códigos; el máximo es 5.000 por archivo.']);
    $this->assertDatabaseCount('product_keys', 0);
});

it('imports a file at the limit, across insert chunks', function () {
    $product = Product::factory()->create();
    $codes = implode("\n", array_map(fn (int $n): string => sprintf('CODE-%05d', $n), range(1, ImportProductKeys::MAX_CODES)));

    $result = importKeys($product, $codes);

    expect($result->imported)->toBe(ImportProductKeys::MAX_CODES)
        ->and($product->keys()->count())->toBe(ImportProductKeys::MAX_CODES);
});

it('never puts a code in the result', function () {
    $product = Product::factory()->create();
    ProductKey::factory()->for($product)->create(['code' => 'TAKEN-00001']);

    $result = importKeys($product, "SECRET-00001\nTAKEN-00001\nSECRET-00001\nBAD");

    expect(serialize($result))->not->toContain('SECRET')->not->toContain('TAKEN');
});
