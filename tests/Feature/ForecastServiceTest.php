<?php

use App\Models\Location;
use App\Services\ForecastService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();

    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'current' => ['time' => '2026-11-05T07:45', 'interval' => 900, 'cloud_cover' => 92],
        ]),
    ]);

    $this->location = Location::factory()->make([
        'latitude' => -23.55,
        'longitude' => -46.63,
    ]);
});

test('it asks Open-Meteo for the current cloud cover of the location', function () {
    app(ForecastService::class)->currentCloudCover($this->location);

    Http::assertSent(function (Request $request) {
        return str_starts_with($request->url(), 'https://api.open-meteo.com/v1/forecast')
            && $request['latitude'] == -23.55
            && $request['longitude'] == -46.63
            && $request['current'] === 'cloud_cover';
    });
});

test('it returns the current cloud cover', function () {
    $cloudCover = app(ForecastService::class)->currentCloudCover($this->location);

    expect($cloudCover)->toBe(92);
});
