<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Http\Client\Factory;

class ForecastService
{
    private const URL = 'https://api.open-meteo.com/v1/forecast';

    public function __construct(private Factory $http) {}

    /**
     * Cloud cover (0 to 100%) at the location right now.
     */
    public function currentCloudCover(Location $location): int
    {
        $response = $this->http->get(self::URL, [
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'current' => 'cloud_cover',
        ])->throw();

        return $response->json('current.cloud_cover');
    }
}
