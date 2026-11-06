<?php

use App\Mail\GoodSkyAlert;
use App\Models\Location;

test('the e-mail shows the location, the score and a link to its sky page', function () {
    $location = Location::factory()->create(['name' => 'Sítio em Cunha']);

    $mailable = new GoodSkyAlert($location, 8.5, 10, 'Nova', 0.02);

    $mailable->assertHasSubject('O céu está bom para observar em Sítio em Cunha');
    $mailable->assertSeeInHtml('Sítio em Cunha');
    $mailable->assertSeeInHtml('8,5');
    $mailable->assertSeeInHtml(route('sky', ['location' => $location->id]));
});
