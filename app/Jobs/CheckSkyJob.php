<?php

namespace App\Jobs;

use App\Mail\GoodSkyAlert;
use App\Models\User;
use App\Services\ForecastService;
use App\Services\MoonPhase;
use App\Services\SkyScore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckSkyJob implements ShouldQueue
{
    use Queueable;

    public const MINIMUM_SCORE = 7;

    public function handle(ForecastService $forecast, MoonPhase $moonPhase, SkyScore $skyScore): void
    {
        $now = now();
        $moonIllumination = $moonPhase->illumination($now);
        $moonName = $moonPhase->name($now);

        $users = User::has('locations')->with('locations')->get();

        foreach ($users as $user) {
            $bestLocation = null;
            $bestScore = null;
            $bestCloudCover = null;

            foreach ($user->locations as $location) {
                try {
                    $cloudCover = $forecast->currentCloudCover($location);
                } catch (HttpClientException $exception) {
                    Log::warning('Open-Meteo unavailable', [
                        'location_id' => $location->id,
                        'error' => $exception->getMessage(),
                    ]);

                    continue;
                }

                $score = $skyScore->calculate($cloudCover, $moonIllumination);

                if ($bestScore === null || $score > $bestScore) {
                    $bestLocation = $location;
                    $bestScore = $score;
                    $bestCloudCover = $cloudCover;
                }
            }

            if ($bestLocation === null || $bestScore < self::MINIMUM_SCORE) {
                continue;
            }

            Mail::to($user)->send(
                new GoodSkyAlert($bestLocation, $bestScore, $bestCloudCover, $moonName, $moonIllumination)
            );
        }
    }
}
