<?php

use App\Services\SkyScore;

test('the score follows the table of examples', function (int $cloudCover, float $moonIllumination, float $score) {
    expect((new SkyScore)->calculate($cloudCover, $moonIllumination))->toBe($score);
})->with([
    'clear sky, new moon' => [0, 0.0, 10.0],
    'clear sky, full moon' => [0, 1.0, 5.0],
    'half cloudy, new moon' => [50, 0.0, 5.0],
    'half cloudy, full moon' => [50, 1.0, 2.5],
    'overcast, full moon' => [100, 1.0, 0.0],
    'overcast, new moon' => [100, 0.0, 0.0],
]);

test('the score is rounded to one decimal place', function () {
    // 10 × 0.67 × (1 − 0.5 × 0.3) = 5.695
    expect((new SkyScore)->calculate(33, 0.3))->toBe(5.7);
});
