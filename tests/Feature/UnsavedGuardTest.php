<?php

use App\Support\Desk\DeskTab;
use Illuminate\Support\Facades\Blade;
use Modules\Core\Models\User;
use Spatie\Permission\Models\Permission;

test('the guard hands its Livewire properties to the browser and draws nothing', function () {
    $one = Blade::render('<x-ui.unsaved-guard watch="form" />');
    $several = Blade::render('<x-ui.unsaved-guard :watch="[\'form\', \'tiers\']" />');

    expect($one)->toContain('data-unsaved-guard')
        ->toContain('hidden')
        ->toContain("unsavedGuard(JSON.parse('[\u0022form\u0022]'))");
    expect($several)->toContain("unsavedGuard(JSON.parse('[\u0022form\u0022,\u0022tiers\u0022]'))");
});

test('every signed-in page carries the one leave question, with keep editing as the way out', function () {
    $this->actingAs(User::factory()->role('MEAL')->create())
        ->get(route('admin.evidence-types.index'))
        ->assertOk()
        ->assertSee('data-unsaved-prompt', false)
        ->assertSee('data-unsaved-discard', false)
        ->assertSee(__('common.discard_changes_question'))
        ->assertSee(__('common.unsaved_leave_text'))
        ->assertSee(__('common.keep_editing'));
});

test('a page in a desk tab carries the question too: it is the page being left', function () {
    $this->actingAs(User::factory()->role('MEAL')->create())
        ->get(route('admin.evidence-types.index', [DeskTab::Param => '1']))
        ->assertOk()
        ->assertSee('data-unsaved-prompt', false);
});

test('the contract form is guarded on its form values and pay tiers', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('contracts.create', 'web'));

    $this->actingAs($user->fresh())
        ->get(route('hr.contracts.create'))
        ->assertOk()
        ->assertSee('data-unsaved-guard', false)
        ->assertSee('[\\u0022form\\u0022,\\u0022tiers\\u0022]', false);
});
