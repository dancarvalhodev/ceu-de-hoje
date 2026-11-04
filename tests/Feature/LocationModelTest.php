<?php

use App\Models\Location;
use App\Models\User;

test('a user has many locations', function () {
    $user = User::factory()->create();
    Location::factory()->count(2)->for($user)->create();

    expect($user->locations)->toHaveCount(2);
});

test('deleting a user deletes their locations', function () {
    $location = Location::factory()->create();

    $location->user->delete();

    expect(Location::find($location->id))->toBeNull();
});
