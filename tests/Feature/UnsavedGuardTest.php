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

test('the offer form and revise pages are guarded on every field of the shared offer form', function () {
    $pages = base_path('modules/Hr/resources/views/pages/recruitment/offers');
    preg_match_all('/wire:model(?:\.[a-z]+)*="([A-Za-z]+)"/', (string) file_get_contents(base_path('modules/Hr/resources/views/components/recruitment/offer-form.blade.php')), $bound);

    expect($bound[1])->not->toBeEmpty();

    foreach (['form', 'revise'] as $page) {
        preg_match('/<x-ui\.unsaved-guard :watch="\[([^\]]+)\]"/', (string) file_get_contents("{$pages}/⚡{$page}.blade.php"), $guard);
        $watched = array_map(fn (string $path): string => trim($path, " '"), explode(',', $guard[1] ?? ''));

        expect(array_diff(array_unique($bound[1]), $watched))->toBeEmpty("offers/{$page} leaves a field unguarded");
    }
});

test('the offer action dialog asks before discarding what was typed into it', function () {
    $show = (string) file_get_contents(base_path('modules/Hr/resources/views/pages/recruitment/offers/⚡show.blade.php'));
    preg_match('/<x-ui\.modal\b.*?data-offer-action-modal/s', $show, $dialog);

    expect($dialog[0] ?? '')->toContain(':discard-guard=')
        ->toContain("'fields' => ['expiresOn', 'responseNote', 'responseEvidence', 'cancelReason', 'cancelNote']")
        ->toContain("'expiresOn' => \$suggestedExpiresOn");

    $rendered = Blade::render('<x-ui.modal title="Send" :discard-guard="[\'fields\' => [\'expiresOn\'], \'original\' => [\'expiresOn\' => \'2026-11-20\']]">body</x-ui.modal>');

    expect($rendered)->toContain('data-discard-guard')
        ->toContain('data-discard-notice')
        ->toContain(__('common.discard_changes_question'));
});
