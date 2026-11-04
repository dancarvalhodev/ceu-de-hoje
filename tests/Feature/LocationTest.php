<?php

use App\Models\Location;
use App\Models\User;

test('a user registers a location under their own account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('locations.store'), [
        'name' => 'Sítio em Cunha',
        'latitude' => -23.074,
        'longitude' => -44.959,
        'timezone' => 'America/Sao_Paulo',
        'user_id' => 999,
    ]);

    $response->assertRedirect(route('locations.index'));

    $location = Location::first();
    expect($location->name)->toBe('Sítio em Cunha');
    expect($location->user_id)->toBe($user->id);
});

test('an empty form returns an error for every field', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('locations.store'), []);

    $response->assertSessionHasErrors(['name', 'latitude', 'longitude', 'timezone']);
    expect(Location::count())->toBe(0);
});

test('out of range coordinates and an unknown timezone are rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('locations.store'), [
        'name' => 'Lugar nenhum',
        'latitude' => 91,
        'longitude' => -181,
        'timezone' => 'Mars/Olympus',
    ]);

    $response->assertSessionHasErrors([
        'latitude' => 'A latitude fica entre -90 e 90.',
        'longitude' => 'A longitude fica entre -180 e 180.',
        'timezone' => 'Escolha um fuso horário da lista.',
    ]);
});

test('the list shows only the logged in user locations', function () {
    $user = User::factory()->create();
    Location::factory()->for($user)->create(['name' => 'Meu quintal']);
    Location::factory()->create(['name' => 'Quintal do vizinho']);

    $response = $this->actingAs($user)->get(route('locations.index'));

    $response->assertOk();
    $response->assertSee('Meu quintal');
    $response->assertDontSee('Quintal do vizinho');
});

test('the owner edits and deletes their location', function () {
    $location = Location::factory()->create();
    $owner = $location->user;

    $this->actingAs($owner)->get(route('locations.edit', $location))->assertOk();

    $this->actingAs($owner)->put(route('locations.update', $location), [
        'name' => 'Novo nome',
        'latitude' => 10,
        'longitude' => 20,
        'timezone' => 'America/Manaus',
    ])->assertRedirect(route('locations.index'));
    expect($location->fresh()->name)->toBe('Novo nome');

    $this->actingAs($owner)->delete(route('locations.destroy', $location))
        ->assertRedirect(route('locations.index'));
    expect(Location::find($location->id))->toBeNull();
});

test('a user cannot edit, update or delete another user location', function () {
    $location = Location::factory()->create(['name' => 'Do vizinho']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get(route('locations.edit', $location))->assertForbidden();

    $this->actingAs($intruder)->put(route('locations.update', $location), [
        'name' => 'Invadido',
        'latitude' => 10,
        'longitude' => 20,
        'timezone' => 'America/Manaus',
    ])->assertForbidden();

    $this->actingAs($intruder)->delete(route('locations.destroy', $location))->assertForbidden();

    expect($location->fresh()->name)->toBe('Do vizinho');
});

test('a visitor is sent to the login page', function () {
    $location = Location::factory()->create();

    $this->get(route('locations.index'))->assertRedirect(route('login'));
    $this->get(route('locations.create'))->assertRedirect(route('login'));
    $this->post(route('locations.store'), [])->assertRedirect(route('login'));
    $this->get(route('locations.edit', $location))->assertRedirect(route('login'));
});
