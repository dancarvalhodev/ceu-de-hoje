<?php

// No proxy sits in front of the app, so X-Forwarded-* headers come from the client and are ignored
test('a forged X-Forwarded-Proto does not change the links', function () {
    $response = $this->withHeader('X-Forwarded-Proto', 'https')->get('/login');

    $response->assertOk();
    $response->assertSee('action="http://localhost/login"', false);
});

test('a forged X-Forwarded-For does not change the client ip', function () {
    $this->withHeader('X-Forwarded-For', '203.0.113.7')->get('/login');

    expect(request()->ip())->toBe('127.0.0.1');
});
