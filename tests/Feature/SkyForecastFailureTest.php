<?php

use App\Models\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Http::preventStrayRequests();

    $this->location = Location::factory()->create();
});

test('an Open-Meteo error shows a message instead of a 500', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response('Internal Server Error', 500)]);

    $response = $this->actingAs($this->location->user)->get(route('sky'));

    $response->assertOk();
    $response->assertSee('Previsão indisponível agora, tente de novo em alguns minutos.');
    $response->assertDontSee('Pontuação de observabilidade');
});

test('a connection failure shows the same message', function () {
    Http::fake(['api.open-meteo.com/*' => Http::failedConnection()]);

    $response = $this->actingAs($this->location->user)->get(route('sky'));

    $response->assertOk();
    $response->assertSee('Previsão indisponível agora, tente de novo em alguns minutos.');
});

test('the failure is logged as a warning with the location', function () {
    Log::spy();
    Http::fake(['api.open-meteo.com/*' => Http::response('Internal Server Error', 500)]);

    $this->actingAs($this->location->user)->get(route('sky'));

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context) => $message === 'Open-Meteo unavailable'
            && $context['location_id'] === $this->location->id);
});

test('a failed forecast is not cached, so the next visit tries again', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response('Internal Server Error', 500)]);

    $this->actingAs($this->location->user)->get(route('sky'));
    $this->actingAs($this->location->user)->get(route('sky'));

    Http::assertSentCount(2);
});
