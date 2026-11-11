<?php

use App\Models\Activity;
use Illuminate\Support\Facades\Broadcast;
use Modules\Core\Models\User;

test('only the owner may listen on a user notification channel', function () {
    [$owner, $other] = Activity::withoutRecording(fn () => User::factory()->count(2)->create());

    $callback = Broadcast::driver()->getChannels()->get('App.Models.User.{id}');

    expect($callback($owner, $owner->id))->toBeTrue()
        ->and($callback($other, $owner->id))->toBeFalse();
});
