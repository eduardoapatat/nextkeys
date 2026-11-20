<?php

use App\Actions\Catalog\DeleteTitle;
use App\Models\Product;
use App\Models\Title;

it('deletes a title without products', function () {
    $title = Title::factory()->create();

    app(DeleteTitle::class)->handle($title);

    $this->assertModelMissing($title);
});

it('refuses to delete a title that has products', function () {
    $title = Title::factory()->create();
    Product::factory()->for($title)->create();

    $errors = validationErrorsOf(fn () => app(DeleteTitle::class)->handle($title));

    expect($errors['title'])->toBe(['Este título tiene productos y no se puede eliminar.']);
    $this->assertModelExists($title);
});
