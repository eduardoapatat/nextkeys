<?php

use App\Models\User;
use App\Notifications\CustomerVerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withHeaders(fromStorefront());
});

/**
 * The API URL the storefront calls for a verification link: the same path
 * parameters and signature the email carries.
 */
function verificationEndpoint(User $customer, array $overrides = []): string
{
    $link = (new CustomerVerifyEmail)->toMail($customer)->actionUrl;
    parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
    $query = [...$query, ...$overrides];

    return route('api.v1.auth.verification.verify', ['id' => $query['id'], 'hash' => $query['hash']], false)
        .'?'.http_build_query(['expires' => $query['expires'], 'signature' => $query['signature']]);
}

it('verifies the email from the link, without a session', function () {
    Event::fake([Verified::class]);
    $customer = User::factory()->unverified()->create();

    $this->postJson(verificationEndpoint($customer))->assertNoContent();

    expect($customer->fresh()?->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatchedTimes(Verified::class, 1);
});

it('accepts a link already used', function () {
    Event::fake([Verified::class]);
    $customer = User::factory()->unverified()->create();
    $endpoint = verificationEndpoint($customer);

    $this->postJson($endpoint)->assertNoContent();
    $this->postJson($endpoint)->assertNoContent();

    Event::assertDispatchedTimes(Verified::class, 1);
});

it('rejects a tampered signature', function () {
    $customer = User::factory()->unverified()->create();

    $this->postJson(verificationEndpoint($customer, ['signature' => str_repeat('0', 64)]))->assertForbidden();

    expect($customer->fresh()?->hasVerifiedEmail())->toBeFalse();
});

it('rejects a link for another email', function () {
    $customer = User::factory()->unverified()->create(['email' => 'old@example.com']);
    $endpoint = verificationEndpoint($customer);
    $customer->forceFill(['email' => 'new@example.com'])->save();

    $this->postJson($endpoint)->assertForbidden();

    expect($customer->fresh()?->hasVerifiedEmail())->toBeFalse();
});

it('rejects an expired link', function () {
    $customer = User::factory()->unverified()->create();
    $endpoint = verificationEndpoint($customer);

    $this->travel(61)->minutes();

    $this->postJson($endpoint)->assertForbidden();
});

it('resends the verification email to the signed in customer', function () {
    Notification::fake();
    $customer = User::factory()->unverified()->create();

    $this->actingAs($customer, 'customer')
        ->postJson(route('api.v1.auth.verification.send'))
        ->assertAccepted();

    Notification::assertSentTo($customer, CustomerVerifyEmail::class);
});

it('sends nothing when the email is already verified', function () {
    Notification::fake();
    $customer = User::factory()->create();

    $this->actingAs($customer, 'customer')
        ->postJson(route('api.v1.auth.verification.send'))
        ->assertNoContent();

    Notification::assertNothingSent();
});
