<?php

use App\Models\User;

test('a user logs in with the right password', function () {
    $user = User::factory()->create(['password' => 'segredo123']);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'segredo123',
    ]);

    $response->assertRedirect(route('locations.index'));
    $this->assertAuthenticatedAs($user);
});

test('a wrong password does not log in', function () {
    $user = User::factory()->create(['password' => 'segredo123']);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'senha-errada',
    ]);

    $response->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
    $this->assertGuest();
});

test('an unknown e-mail gets the same error as a wrong password', function () {
    $response = $this->post(route('login.store'), [
        'email' => 'ninguem@example.com',
        'password' => 'segredo123',
    ]);

    $response->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
    $this->assertGuest();
});

test('a visitor is sent to the login page from a protected route', function () {
    $response = $this->get(route('locations.index'));

    $response->assertRedirect(route('login'));
});

test('a logged in user cannot open the login page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('login'));

    $response->assertRedirect(route('home'));
});
