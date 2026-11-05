<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\Factory;

class ForecastService
{
    private const URL = 'https://api.open-meteo.com/v1/forecast';

    private const CACHE_SECONDS = 3600;

    public function __construct(
        private Factory $http,
        private Repository $cache,
    ) {}

    /**
     * Cloud cover (0 to 100%) at the location right now.
     */
    public function currentCloudCover(Location $location): int
    {
        $cacheKey = "cloud-cover:{$location->latitude},{$location->longitude}";

        return $this->cache->remember($cacheKey, self::CACHE_SECONDS, function () use ($location) {
            $response = $this->http->get(self::URL, [
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'current' => 'cloud_cover',
            ])->throw();

            return $response->json('current.cloud_cover');
        });
    }
}
