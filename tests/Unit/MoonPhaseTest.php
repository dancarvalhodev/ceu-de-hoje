<?php

use App\Services\MoonPhase;
use Carbon\CarbonImmutable;

test('a new moon is almost not lit', function () {
    $newMoon = CarbonImmutable::parse('2000-01-06 18:14', 'UTC');

    expect((new MoonPhase)->illumination($newMoon))->toEqualWithDelta(0, 0.01);
});

test('a full moon is almost fully lit', function () {
    $fullMoon = CarbonImmutable::parse('2025-01-13 22:27', 'UTC');

    expect((new MoonPhase)->illumination($fullMoon))->toEqualWithDelta(1, 0.01);
});

test('the phase name is in Portuguese', function (string $moment, string $name) {
    $date = CarbonImmutable::parse($moment, 'UTC');

    expect((new MoonPhase)->name($date))->toBe($name);
})->with([
    'new moon' => ['2000-01-06 18:14', 'Nova'],
    'first quarter' => ['2025-01-06 23:56', 'Crescente'],
    'full moon' => ['2025-01-13 22:27', 'Cheia'],
    'last quarter' => ['2025-01-21 20:31', 'Minguante'],
]);
