<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('a visitor registers and is logged in', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Ana Souza',
        'email' => 'ana@example.com',
        'password' => 'segredo123',
        'password_confirmation' => 'segredo123',
    ]);

    $response->assertRedirect(route('locations.index'));

    $user = User::where('email', 'ana@example.com')->first();
    expect($user)->not->toBeNull();
    expect(Hash::check('segredo123', $user->password))->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

test('registration with an e-mail already in use fails', function () {
    User::factory()->create(['email' => 'ana@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Outra Ana',
        'email' => 'ana@example.com',
        'password' => 'segredo123',
        'password_confirmation' => 'segredo123',
    ]);

    $response->assertSessionHasErrors(['email' => 'Já existe uma conta com este e-mail.']);
    $this->assertGuest();
});

test('a logged in user cannot open the register page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('register'));

    $response->assertRedirect(route('home'));
});
