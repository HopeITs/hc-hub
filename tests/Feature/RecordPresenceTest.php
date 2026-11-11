<?php

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;

/*
| Every signed-in request stamps users.last_seen_at, at most once a minute
| (RecordPresence), for who is online on the HR clock card.
*/

test('a signed-in request stamps the account as seen, at most once a minute', function () {
    $this->travelTo('2026-11-11 09:00:00');
    $user = User::factory()->role('Team Member')->create();
    $seen = fn (): ?string => DB::table('users')->where('id', $user->id)->value('last_seen_at');

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    expect($seen())->toBe('2026-11-11 09:00:00');

    $this->travelTo('2026-11-11 09:00:40');
    $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
    expect($seen())->toBe('2026-11-11 09:00:00');

    $this->travelTo('2026-11-11 09:01:05');
    $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
    expect($seen())->toBe('2026-11-11 09:01:05');
});
