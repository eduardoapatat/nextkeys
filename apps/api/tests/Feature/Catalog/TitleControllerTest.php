<?php

use App\Models\Product;
use App\Models\Title;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('lists titles with their product count', function () {
    $title = Title::factory()->create(['name' => 'Starfall']);
    Product::factory()->for($title)->count(2)->create();
    Title::factory()->create(['name' => 'Neon Heist']);

    $this->get(route('titles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/titles/Index')
            ->where('titles.meta.total', 2)
            ->where('titles.data.0.name', 'Neon Heist')
            ->where('titles.data.1.products_count', 2));
});

it('creates a title', function () {
    $this->post(route('titles.store'), ['name' => 'Starfall', 'description' => 'A space game.'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('titles.index'))
        ->assertInertiaFlash('toast.message', 'Título creado.');

    $this->assertDatabaseHas('titles', ['name' => 'Starfall', 'description' => 'A space game.']);
});

it('requires a name and a description', function () {
    $this->post(route('titles.store'), [])
        ->assertSessionHasErrors([
            'name' => 'El campo nombre es obligatorio.',
            'description' => 'El campo descripción es obligatorio.',
        ]);
});

it('updates a title', function () {
    $title = Title::factory()->create();

    $this->put(route('titles.update', $title), ['name' => 'Starfall II', 'description' => 'The sequel.'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('titles.index'));

    expect($title->refresh())->name->toBe('Starfall II')->description->toBe('The sequel.');
});

it('refuses to delete a title with products', function () {
    $title = Title::factory()->create();
    Product::factory()->for($title)->create();

    $this->from(route('titles.index'))
        ->delete(route('titles.destroy', $title))
        ->assertSessionHasErrors(['title' => 'Este título tiene productos y no se puede eliminar.']);

    $this->assertModelExists($title);
});

it('deletes a title without products', function () {
    $title = Title::factory()->create();

    $this->delete(route('titles.destroy', $title))->assertRedirect(route('titles.index'));

    $this->assertModelMissing($title);
});
