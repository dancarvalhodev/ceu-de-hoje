<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Solaris\MoonPhase as MoonCalculator;
use Solaris\MoonPhaseName;

class MoonPhase
{
    /**
     * Fraction of the moon that is lit: 0 = new moon, 1 = full moon.
     */
    public function illumination(CarbonInterface $moment): float
    {
        $moon = new MoonCalculator($moment);

        return $moon->getIllumination();
    }

    public function name(CarbonInterface $moment): string
    {
        $moon = new MoonCalculator($moment);

        return match ($moon->getPhaseNameEnum()) {
            MoonPhaseName::NEW_MOON => 'Nova',
            MoonPhaseName::FULL_MOON => 'Cheia',
            MoonPhaseName::WAXING_CRESCENT, MoonPhaseName::FIRST_QUARTER, MoonPhaseName::WAXING_GIBBOUS => 'Crescente',
            default => 'Minguante',
        };
    }
}
