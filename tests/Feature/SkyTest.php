<?php

use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();

    Http::fake([
        'api.open-meteo.com/*' => Http::response(['current' => ['cloud_cover' => 0]]),
    ]);

    // Full moon: with a clear sky the score is 5,0.
    $this->travelTo(Carbon::parse('2025-01-13 22:27', 'UTC'));
});

test('the owner sees the score of their location', function () {
    $location = Location::factory()->create(['name' => 'Sítio em Cunha']);

    $response = $this->actingAs($location->user)->get(route('sky'));

    $response->assertOk();
    $response->assertSee('Sítio em Cunha');
    $response->assertSee('5,0');
    $response->assertSee('Cheia');
    $response->assertSee('0%');
});

test('without a location in the URL it opens the first one in alphabetical order', function () {
    $user = User::factory()->create();
    Location::factory()->for($user)->create(['name' => 'Zona rural', 'latitude' => 10]);
    Location::factory()->for($user)->create(['name' => 'Avenida', 'latitude' => 20]);

    $this->actingAs($user)->get(route('sky'))->assertOk();

    Http::assertSent(fn (Request $request) => $request['latitude'] == 20);
});

test('the user switches location through the URL', function () {
    $user = User::factory()->create();
    Location::factory()->for($user)->create(['name' => 'Avenida', 'latitude' => 20]);
    $other = Location::factory()->for($user)->create(['name' => 'Zona rural', 'latitude' => 10]);

    $this->actingAs($user)->get(route('sky', ['location' => $other->id]))->assertOk();

    Http::assertSent(fn (Request $request) => $request['latitude'] == 10);
    Http::assertSentCount(1);
});

test('a location of another user returns 404', function () {
    $user = User::factory()->create();
    Location::factory()->for($user)->create();
    $neighbor = Location::factory()->create();

    $this->actingAs($user)->get(route('sky', ['location' => $neighbor->id]))->assertNotFound();

    Http::assertNothingSent();
});

test('a user without locations sees a link to register one', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('sky'));

    $response->assertOk();
    $response->assertSee('Você ainda não cadastrou nenhum lugar');
    $response->assertSee(route('locations.create'));
    Http::assertNothingSent();
});

test('a visitor is sent to the login page', function () {
    $this->get(route('sky'))->assertRedirect(route('login'));
});
