<?php

namespace App\Http\Controllers;

use App\Services\ForecastService;
use App\Services\MoonPhase;
use App\Services\SkyScore;
use Illuminate\Http\Request;
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
        $cloudCover = $forecast->currentCloudCover($location);
        $moonIllumination = $moonPhase->illumination($now);

        return view('sky', [
            'locations' => $locations,
            'location' => $location,
            'cloudCover' => $cloudCover,
            'moonName' => $moonPhase->name($now),
            'moonIllumination' => $moonIllumination,
            'score' => $skyScore->calculate($cloudCover, $moonIllumination),
        ]);
    }
}
