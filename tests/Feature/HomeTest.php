<?php

use App\Models\User;

test('guest sees the login and register links', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Entrar');
    $response->assertSee('Criar conta');
    $response->assertDontSee('Sair');
});

test('logged in user sees only the logout button', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
    $response->assertSee('Sair');
    $response->assertDontSee('Entrar');
    $response->assertDontSee('Criar conta');
});
