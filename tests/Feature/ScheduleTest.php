<?php

use App\Jobs\CheckSkyJob;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

test('CheckSkyJob runs every day at 20:00 in São Paulo', function () {
    $events = collect(app(Schedule::class)->events());

    $event = $events->first(fn (Event $event) => $event->description === CheckSkyJob::class);

    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('0 20 * * *');
    expect($event->timezone)->toBe('America/Sao_Paulo');
});
