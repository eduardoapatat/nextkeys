<?php

use App\Actions\Customers\AddressData;
use App\Models\CustomerAddress;
use App\Models\User;

beforeEach(function () {
    $this->withHeaders(fromStorefront());
    $this->customer = User::factory()->create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function addressPayload(array $overrides = []): array
{
    return [
        'recipient_name' => 'Ana Pérez',
        'phone' => '+56 9 1234 5678',
        'region' => 'RM',
        'commune' => 'Ñuñoa',
        'street' => 'Av. Irarrázaval',
        'street_number' => '1234',
        'apartment' => 'Depto 501',
        ...$overrides,
    ];
}

it('saves an address with a normalized phone and answers with its public id', function () {
    $response = $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.account.addresses.store'), addressPayload())
        ->assertCreated()
        ->assertJsonPath('data.phone', '+56912345678')
        ->assertJsonPath('data.region', ['code' => 'RM', 'name' => 'Metropolitana de Santiago'])
        ->assertJsonPath('data.commune', 'Ñuñoa')
        ->assertJsonPath('data.apartment', 'Depto 501')
        ->assertJsonMissingPath('data.id');

    $address = $this->customer->addresses()->sole();
    $response->assertJsonPath('data.public_id', $address->public_id);
});

it('lists only the customer\'s own addresses', function () {
    $own = CustomerAddress::factory()->for($this->customer)->create();
    CustomerAddress::factory()->create();

    $this->actingAs($this->customer, 'customer')
        ->getJson(route('api.v1.account.addresses.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.public_id', $own->public_id);
});

it('updates and deletes an address', function () {
    $address = CustomerAddress::factory()->for($this->customer)->create();

    $this->actingAs($this->customer, 'customer')
        ->putJson(route('api.v1.account.addresses.update', $address->public_id), addressPayload(['region' => 'VS', 'commune' => 'Viña del Mar', 'apartment' => '']))
        ->assertOk()
        ->assertJsonPath('data.region.code', 'VS')
        ->assertJsonPath('data.apartment', null);

    $this->deleteJson(route('api.v1.account.addresses.destroy', $address->public_id))->assertNoContent();

    expect(CustomerAddress::query()->count())->toBe(0);
});

it('answers 404 for another customer\'s address', function (string $method, string $route) {
    $address = CustomerAddress::factory()->create();

    $this->actingAs($this->customer, 'customer')
        ->json($method, route($route, $address->public_id), addressPayload())
        ->assertNotFound();

    expect($address->fresh())->not->toBeNull()
        ->and($address->fresh()?->street)->toBe($address->street);
})->with([
    'update' => ['PUT', 'api.v1.account.addresses.update'],
    'delete' => ['DELETE', 'api.v1.account.addresses.destroy'],
]);

it('rejects a commune from another region', function () {
    $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.account.addresses.store'), addressPayload(['region' => 'VS']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['commune' => 'Elige una comuna de la región seleccionada.']);
});

it('rejects a phone that is not a Chilean mobile', function (string $phone) {
    $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.account.addresses.store'), addressPayload(['phone' => $phone]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['phone' => 'Ingresa un celular chileno, por ejemplo +56 9 1234 5678.']);
})->with(['landline' => '+56 2 2123 4567', 'short' => '9 1234 567', 'foreign' => '+54 9 1234 5678']);

it('normalizes the ways a Chilean mobile is written', function (string $phone) {
    expect(AddressData::normalizePhone($phone))->toBe('+56912345678');
})->with(['+56912345678', '56912345678', '912345678', '+56 9 1234 5678', '(+56) 9-1234-5678']);

it('rejects an unknown region and missing fields', function () {
    $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.account.addresses.store'), addressPayload(['region' => 'XX', 'street' => '']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['region', 'street']);
});

it('keeps at most five addresses per customer', function () {
    CustomerAddress::factory()->for($this->customer)->count(5)->create();

    $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.account.addresses.store'), addressPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['address' => 'Puedes guardar hasta 5 direcciones. Borra una para agregar otra.']);

    expect($this->customer->addresses()->count())->toBe(5);
});

it('asks for a signed in customer', function () {
    $this->getJson(route('api.v1.account.addresses.index'))->assertUnauthorized();
});

it('serves the shipping fee and the regions with their communes', function () {
    config(['orders.shipping_cost' => 3990]);

    $this->getJson(route('api.v1.shipping.show'))
        ->assertOk()
        ->assertJsonPath('data.cost', 3990)
        ->assertJsonCount(16, 'data.regions')
        ->assertJsonPath('data.regions.0', ['code' => 'AP', 'name' => 'Arica y Parinacota', 'communes' => ['Arica', 'Camarones', 'Putre', 'General Lagos']]);
});
