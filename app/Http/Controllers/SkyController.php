<?php

namespace App\Http\Controllers;

use App\Services\ForecastService;
use App\Services\MoonPhase;
use App\Services\SkyScore;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SkyController extends Controller
{
    public function __invoke(Request $request, ForecastService $forecast, MoonPhase $moonPhase, SkyScore $skyScore): View
    {
        $locations = $request->user()->locations()->orderBy('name')->get();

        if ($locations->isEmpty()) {
            return view('sky', ['locations' => $locations]);
        }

        $location = $locations->first();
        if ($request->filled('location')) {
            $location = $request->user()->locations()->findOrFail($request->query('location'));
        }

        $now = now();
        $moonIllumination = $moonPhase->illumination($now);

        $cloudCover = null;
        $score = null;
        try {
            $cloudCover = $forecast->currentCloudCover($location);
            $score = $skyScore->calculate($cloudCover, $moonIllumination);
        } catch (HttpClientException $exception) {
            // The view shows "forecast unavailable"; nothing was cached, so the next visit retries.
            Log::warning('Open-Meteo unavailable', [
                'location_id' => $location->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return view('sky', [
            'locations' => $locations,
            'location' => $location,
            'cloudCover' => $cloudCover,
            'moonName' => $moonPhase->name($now),
            'moonIllumination' => $moonIllumination,
            'score' => $score,
        ]);
    }
}
