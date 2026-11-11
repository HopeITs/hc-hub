<?php

use App\Support\Desk\Destinations;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/*
| What several Desk parts read is read once per request: each page's access
| rule (Destinations, asked by the sidebar, the Desk and its page cards) and
| the funding totals (At a glance and Funding position).
*/

test('the General Manager desk reads each page access rule and the funding totals once', function () {
    Livewire::withoutLazyLoading();
    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $this->actingAs(userWithRole('General Manager'))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-panel="money"', false)
        ->assertSee('data-panel="glance"', false);

    $running = fn (string $text): int => count(array_filter($queries, fn (string $sql): bool => str_contains($sql, $text)));

    expect($running('recruitment_application_participants'))->toBe(1)
        ->and($running('sum(budget)'))->toBe(1);
});

test('a page access rule is read again once the viewer gains a permission in the same request', function () {
    $user = userWithRole('Team Member');
    $destinations = app(Destinations::class);

    expect($destinations->allows($user, 'donors.index'))->toBeFalse();

    $user->givePermissionTo(Permission::findOrCreate('donors.view'));

    expect($destinations->allows($user->fresh(), 'donors.index'))->toBeTrue();
});
