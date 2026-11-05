<?php

namespace App\Services;

class SkyScore
{
    /**
     * Observability score from 0 to 10. It starts at 10 and loses for two reasons:
     * - clouds take away in the same proportion (30% clouds keeps 70% of the score);
     * - the moon takes away up to half (new moon: nothing; half moon: a quarter; full moon: half).
     *
     * score = 10 × (1 − cloudCover / 100) × (1 − 0.5 × moonIllumination)
     *
     * @param  int  $cloudCover  0 to 100 (%)
     * @param  float  $moonIllumination  0 (new) to 1 (full)
     */
    public function calculate(int $cloudCover, float $moonIllumination): float
    {
        $clearSky = 1 - $cloudCover / 100;
        $darkSky = 1 - 0.5 * $moonIllumination;
        $score = 10 * $clearSky * $darkSky;

        return round($score, 1);
    }
}
