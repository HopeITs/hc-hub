<?php

use App\Http\Middleware\RemembersRecentRecord;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('a person can pin a sidebar page and unpin it again', function () {
    $user = User::factory()->role('Super Admin')->create();

    $this->actingAs($user)->postJson(route('nav.pins.toggle'), ['route' => 'transfers.index'])
        ->assertOk()
        ->assertJson(['pins' => ['transfers.index']]);

    expect($user->fresh()->nav_pins)->toBe(['transfers.index']);

    $this->actingAs($user)->postJson(route('nav.pins.toggle'), ['route' => 'transfers.index'])
        ->assertOk()
        ->assertJson(['pins' => []]);

    expect($user->fresh()->nav_pins)->toBe([]);
});

test('a page the sidebar does not offer the person cannot be pinned', function () {
    $user = User::factory()->role('Team Member')->create();

    $this->actingAs($user)->postJson(route('nav.pins.toggle'), ['route' => 'admin.users.index'])
        ->assertUnprocessable();

    expect($user->fresh()->nav_pins)->toBeNull();
});

test('pinned pages show in the sidebar', function () {
    $user = User::factory()->role('Super Admin')->create(['nav_pins' => ['transfers.index', 'gone.page']]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-side-personal', false)
        ->assertSee('transfers.index"', false)
        ->assertDontSee('gone.page', false);
});

test('opening a project remembers it as a recent record, newest first and once', function () {
    $user = User::factory()->role('Super Admin')->create();
    $first = Project::factory()->create(['name' => 'Gaza WASH Recovery']);
    $second = Project::factory()->create(['name' => 'Shelter Winterisation']);

    $this->actingAs($user)->get(route('projects.show', $first))->assertOk();
    $this->actingAs($user)->get(route('projects.show', $second))->assertOk();
    $this->actingAs($user)->get(route('projects.show', $first))->assertOk();

    expect(collect($user->fresh()->nav_recent)->pluck('label')->all())->toBe(['Gaza WASH Recovery', 'Shelter Winterisation']);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertSee('data-side-recent', false)
        ->assertSee('Gaza WASH Recovery');
});

test('the recent list keeps only the last few records', function () {
    $user = User::factory()->role('Super Admin')->create();

    Project::factory()->count(RemembersRecentRecord::Limit + 2)->create()
        ->each(fn (Project $project) => $this->actingAs($user)->get(route('projects.show', $project))->assertOk());

    expect($user->fresh()->nav_recent)->toHaveCount(RemembersRecentRecord::Limit);
});

test('inside a project the sidebar leads with that project\'s sections', function () {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->role('Super Admin')->create())
        ->get(route('projects.show', [$project, 'tab' => 'tasks']))
        ->assertOk()
        ->assertSee('data-side-context="'.$project->getRouteKey().'"', false)
        ->assertSee('data-side-section="tasks"', false)
        ->assertSee('data-side-context-back', false);

    $this->actingAs(User::factory()->role('Super Admin')->create())
        ->get(route('projects.index'))
        ->assertDontSee('data-side-context=', false);
});

test('a group glows when it holds something of the viewer\'s due within days', function () {
    $user = User::factory()->role('Super Admin')->create();
    Task::factory()->create(['created_by' => $user->id, 'due_date' => today()->addDays(2), 'department_id' => null]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['data-side-group-toggle="workspace"', 'data-side-due'], false);

    $this->actingAs(User::factory()->role('Super Admin')->create())->get(route('dashboard'))
        ->assertDontSee('data-side-due', false);
});
