<?php

test('links use https when the proxy says the request came over https', function () {
    $response = $this->withHeader('X-Forwarded-Proto', 'https')->get('/login');

    $response->assertOk();
    $response->assertSee('action="https://localhost/login"', false);
});

test('links stay http without the proxy header', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSee('action="http://localhost/login"', false);
});
