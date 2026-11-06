<?php

use App\Jobs\CheckSkyJob;
use App\Mail\GoodSkyAlert;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Fakes Open-Meteo with one cloud cover per latitude; null answers with a 500.
 *
 * @param  array<int, int|null>  $cloudCoverByLatitude
 */
function fakeCloudCover(array $cloudCoverByLatitude): void
{
    Http::fake(function (Request $request) use ($cloudCoverByLatitude) {
        $cloudCover = $cloudCoverByLatitude[(int) $request['latitude']];

        if ($cloudCover === null) {
            return Http::response('Internal Server Error', 500);
        }

        return Http::response(['current' => ['cloud_cover' => $cloudCover]]);
    });
}

beforeEach(function () {
    Http::preventStrayRequests();
    Mail::fake();

    // New moon: the score depends only on the clouds (clear sky = 10).
    $this->travelTo(Carbon::parse('2000-01-06 18:14', 'UTC'));
});

test('a clear sky sends the alert to the location owner', function () {
    $location = Location::factory()->create(['latitude' => 10]);
    fakeCloudCover([10 => 0]);

    CheckSkyJob::dispatchSync();

    Mail::assertSent(GoodSkyAlert::class, fn (GoodSkyAlert $mail) => $mail->hasTo($location->user->email));
});

test('a score below the minimum sends nothing', function () {
    Location::factory()->create(['latitude' => 10]);
    fakeCloudCover([10 => 50]);

    CheckSkyJob::dispatchSync();

    Mail::assertNothingSent();
});

test('a user with several locations gets one e-mail with the best one', function () {
    $user = User::factory()->create();
    Location::factory()->for($user)->create(['name' => 'Cidade', 'latitude' => 10]);
    Location::factory()->for($user)->create(['name' => 'Sítio', 'latitude' => 20]);
    fakeCloudCover([10 => 20, 20 => 0]);

    CheckSkyJob::dispatchSync();

    Mail::assertSentCount(1);
    Mail::assertSent(GoodSkyAlert::class, fn (GoodSkyAlert $mail) => $mail->location->name === 'Sítio');
});

test('a user without locations is skipped', function () {
    User::factory()->create();

    CheckSkyJob::dispatchSync();

    Http::assertNothingSent();
    Mail::assertNothingSent();
});

test('a failure in one location does not stop the others', function () {
    Log::spy();
    $user = User::factory()->create();
    $broken = Location::factory()->for($user)->create(['latitude' => 10]);
    Location::factory()->for($user)->create(['name' => 'Sítio', 'latitude' => 20]);
    fakeCloudCover([10 => null, 20 => 0]);

    CheckSkyJob::dispatchSync();

    Mail::assertSent(GoodSkyAlert::class, fn (GoodSkyAlert $mail) => $mail->location->name === 'Sítio');
    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context) => $context['location_id'] === $broken->id);
});
