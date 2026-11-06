<?php

use App\Enums\ProjectStatus;
use App\Http\Middleware\SyncProgramsFromHr;
use App\Models\AnnualPlan;
use App\Models\PlanIndicator;
use App\Models\Program;
use App\Models\Project;
use App\Models\Strategy;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('guests are redirected from program management', function () {
    $this->get(route('programs.index'))->assertRedirect(route('login'));
});

test('all authenticated roles can access program management', function (string $roleName) {
    $user = User::factory()->role($roleName)->create();

    $this->actingAs($user)
        ->get(route('programs.index'))
        ->assertOk()
        ->assertSeeText('Programs')
        ->assertSee(route('programs.index'), false);
})->with([
    'team member' => 'Team Member',
    'manager' => 'Managers',
    'meal' => 'MEAL',
    'admin' => 'Super Admin',
]);

test('authenticated users can search and filter programs by strategy and manager', function () {
    $user = User::factory()->role('General Manager')->create();
    $firstManager = insertHrEmployee(firstNameEn: 'Layla', lastNameEn: 'Haddad');
    $secondManager = insertHrEmployee(firstNameEn: 'Omar', lastNameEn: 'Khalil');
    $firstStrategy = Strategy::factory()->create(['name' => 'Country Strategy', 'start_year' => 2026, 'end_year' => 2030]);
    $secondStrategy = Strategy::factory()->create(['name' => 'Regional Strategy', 'start_year' => 2031, 'end_year' => 2035]);

    Program::factory()->create([
        'name' => 'Water Security',
        'strategy_id' => $firstStrategy->id,
        'manager_id' => $firstManager,
    ]);
    Program::factory()->create([
        'name' => 'Safe Shelter',
        'strategy_id' => $secondStrategy->id,
        'manager_id' => $secondManager,
    ]);

    Livewire::actingAs($user)
        ->test('pages::programs.index')
        ->assertSee('Water Security')
        ->assertSee('Safe Shelter')
        ->set('search', 'Layla')
        ->assertSee('Water Security')
        ->assertDontSee('Safe Shelter')
        ->call('clearFilters')
        ->set('strategy', (string) $secondStrategy->id)
        ->assertDontSee('Water Security')
        ->assertSee('Safe Shelter')
        ->call('clearFilters')
        ->set('manager', (string) $firstManager)
        ->assertSee('Water Security')
        ->assertDontSee('Safe Shelter')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('strategy', '')
        ->assertSet('manager', '')
        ->assertSee('Water Security')
        ->assertSee('Safe Shelter');
});

test('an hr program can be linked to a strategy and manager without changing its hr name', function () {
    $user = User::factory()->role('General Manager')->create();
    $strategy = Strategy::factory()->create();
    $manager = insertHrEmployee();
    $program = Program::factory()->unassigned()->create(['name' => 'Hope Center']);

    Livewire::actingAs($user)
        ->test('pages::programs.index')
        ->call('openEditModal', $program->id)
        ->assertSet('editingProgramName', 'Hope Center')
        ->assertSet('strategyId', '')
        ->assertSet('managerId', '')
        ->set('strategyId', (string) $strategy->id)
        ->set('managerId', (string) $manager)
        ->call('saveProgram')
        ->assertHasNoErrors()
        ->assertSet('showProgramModal', false);

    expect($program->refresh())
        ->name->toBe('Hope Center')
        ->strategy_id->toBe($strategy->id)
        ->manager_id->toBe($manager)
        ->and(DB::connection('hr')->table('programs')->where('id', $program->id)->value('name'))->toBe('Hope Center');
});

test('a program assignment can be cleared back to not assigned', function () {
    $program = Program::factory()->create();

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->call('openEditModal', $program->id)
        ->set('strategyId', '')
        ->set('managerId', '')
        ->call('saveProgram')
        ->assertHasNoErrors();

    expect($program->refresh())
        ->strategy_id->toBeNull()
        ->manager_id->toBeNull();
});

test('unassigned programs are listed as not assigned', function () {
    Program::factory()->unassigned()->create(['name' => 'Study Zone']);

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->assertSee('Study Zone')
        ->assertSee('Not assigned');
});

test('strategy and manager assignments must reference available records', function () {
    $program = Program::factory()->unassigned()->create();

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->call('openEditModal', $program->id)
        ->set('strategyId', '999999')
        ->set('managerId', '999999')
        ->call('saveProgram')
        ->assertHasErrors(['strategyId' => 'exists', 'managerId' => 'exists'])
        ->assertSee('The selected strategy is no longer available.')
        ->assertSee('The selected manager is no longer available.');

    expect($program->refresh()->strategy_id)->toBeNull();
});

test('the directory offers no way to create programs', function () {
    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->assertDontSee('Add program')
        ->assertDontSee('data-test="create-program-button"', false);
});

test('program strategy and manager names are escaped in the directory', function () {
    $user = User::factory()->role('General Manager')->create();
    $unsafeProgram = '<script>alert("program")</script>';
    $unsafeStrategy = '<script>alert("strategy")</script>';
    $unsafeManager = '<script>alert("manager")</script>';
    $strategy = Strategy::factory()->create(['name' => $unsafeStrategy]);
    $manager = insertHrEmployee(firstNameEn: $unsafeManager, lastNameEn: '');
    Program::factory()->create([
        'name' => $unsafeProgram,
        'strategy_id' => $strategy->id,
        'manager_id' => $manager,
    ]);

    Livewire::actingAs($user)
        ->test('pages::programs.index')
        ->assertSee($unsafeProgram)
        ->assertSee($unsafeStrategy)
        ->assertSee($unsafeManager)
        ->assertDontSee($unsafeProgram, false)
        ->assertDontSee($unsafeStrategy, false)
        ->assertDontSee($unsafeManager, false);
});

test('program management explains that programs come from hr when there are none', function () {
    $this->actingAs(User::factory()->role('General Manager')->create())
        ->get(route('programs.index'))
        ->assertOk()
        ->assertSeeText('No programs from HR yet')
        ->assertSeeText('Programs appear here once they are added in the HR system.');
});

test('each program is a row that opens its details', function () {
    $program = Program::factory()->create(['name' => 'Water Security']);

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->assertSet('visibleProgramCount', 8)
        ->assertSee('Water Security')
        ->assertSee(route('programs.show', $program), false)
        ->assertDontSee('Programs come from the HR system.')
        ->assertDontSee('matching program');
});

test('the program list loads eight initially and four more on request', function () {
    foreach (range(1, 9) as $number) {
        Program::factory()->create(['name' => 'Program '.str_pad((string) $number, 2, '0', STR_PAD_LEFT)]);
    }

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->assertSet('visibleProgramCount', 8)
        ->assertSee('Program 08')
        ->assertDontSee('Program 09')
        ->assertSee('Load more')
        ->call('loadMore')
        ->assertSet('visibleProgramCount', 12)
        ->assertSee('Program 09');
});

test('program gap filter focuses programs without a manager', function () {
    Program::factory()->create(['name' => 'Managed Program']);
    Program::factory()->unassigned()->create(['name' => 'Unmanaged Program']);

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->set('gap', 'no_manager')
        ->assertSee('Unmanaged Program')
        ->assertDontSee('Managed Program');
});

test('program filter pickers apply strategy and manager selections', function () {
    $strategy = Strategy::factory()->create(['name' => 'Country Strategy']);
    $manager = insertHrEmployee(firstNameEn: 'Amal', lastNameEn: 'Manager');
    Program::factory()->create(['name' => 'Selected Program', 'strategy_id' => $strategy->id, 'manager_id' => $manager]);
    Program::factory()->create(['name' => 'Other Program']);

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->call('filterByStrategy', $strategy->id)
        ->assertSet('strategy', (string) $strategy->id)
        ->assertSee('Selected Program')
        ->assertDontSee('Other Program')
        ->call('filterByManager', $manager)
        ->assertSet('manager', (string) $manager)
        ->assertSee('Selected Program')
        ->assertDontSee('Other Program');
});

test('visiting the app mirrors new hr programs as unassigned', function () {
    Cache::forget(SyncProgramsFromHr::CacheKey);
    $hrProgramId = insertHrProgram('Children University');

    $this->actingAs(User::factory()->role('General Manager')->create())
        ->get(route('programs.index'))
        ->assertOk()
        ->assertSeeText('Children University');

    expect(Program::query()->findOrFail($hrProgramId))
        ->name->toBe('Children University')
        ->strategy_id->toBeNull()
        ->manager_id->toBeNull();
});

test('the assignment form is rendered only while its modal is open', function () {
    $user = User::factory()->role('General Manager')->create();
    $program = Program::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::programs.index')
        ->assertDontSeeHtml('wire:model="managerId"')
        ->call('openEditModal', $program->id)
        ->assertSeeHtml('wire:model="managerId"');
});

test('the strategy and manager filters can list programs nobody is linked to', function () {
    Program::factory()->create(['name' => 'Linked Program']);
    Program::factory()->unassigned()->create(['name' => 'Orphan Program']);

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->set('strategy', 'none')
        ->assertSee('Orphan Program')
        ->assertDontSee('Linked Program')
        ->set('strategy', '')
        ->set('manager', 'none')
        ->assertSee('Orphan Program')
        ->assertDontSee('Linked Program');
});

test('the manager filter lists only people who manage a program, with their count', function () {
    $manager = insertHrEmployee(firstNameEn: 'Amal', lastNameEn: 'Manager');
    insertHrEmployee(firstNameEn: 'Idle', lastNameEn: 'Bystander');
    Program::factory()->count(2)->create(['manager_id' => $manager]);
    Program::factory()->unassigned()->create();

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->assertSee('Amal Manager')
        ->assertSee('(2)')
        ->assertDontSee('Idle Bystander')
        ->assertSee('Not assigned (1)');
});

test('each row says what is late, at risk or unfunded in its projects', function () {
    $this->travelTo('2026-09-15 09:00:00');

    $troubled = Program::factory()->create(['name' => 'Troubled Program']);
    Project::factory()->create(['program_id' => $troubled->id, 'status' => ProjectStatus::Active, 'end_date' => '2026-08-01']);
    Program::factory()->create(['name' => 'Empty Program']);

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->assertSeeHtml('data-program-health="critical"')
        ->assertSee('1 project overdue')
        ->assertSee('1 project not funded yet')
        ->assertSeeHtml('data-program-health="none"')
        ->assertSee('No projects yet');
});

test('a program with no strategy reads "Not linked yet" with an Assign shortcut', function () {
    Program::factory()->unassigned()->create(['name' => 'Orphan Program']);

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->assertSeeHtml('data-program-unlinked')
        ->assertSee('Not linked yet')
        ->assertSee('Assign a strategy and manager to Orphan Program');
});

test('the list says how many of the programs are showing', function () {
    foreach (range(1, 9) as $number) {
        Program::factory()->create(['name' => 'Program '.str_pad((string) $number, 2, '0', STR_PAD_LEFT)]);
    }

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->assertSee('Showing 8 of 9 programs');
});

test('an empty search offers one reset button, not two', function () {
    Program::factory()->create(['name' => 'Water Security']);

    $html = Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->set('search', 'zzzz-no-match')
        ->assertSee('No matching programs')
        ->html();

    expect(substr_count($html, 'Reset filters'))->toBe(1);
});

test('the assignment sheet names what it assigns and guards unsaved changes', function () {
    $program = Program::factory()->unassigned()->create(['name' => 'Water Security']);

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->assertDontSeeHtml('data-discard-guard')
        ->call('openEditModal', $program->id)
        ->assertSee('Link Water Security to a strategy and manager')
        ->assertSeeHtml('data-discard-guard')
        ->assertSet('originalStrategyId', '')
        ->assertSeeText('Discard your changes?');
});

test('the no plan gap lists the strategy programs without a plan for this year', function () {
    $this->travelTo('2026-06-15 09:00:00');
    $strategy = Strategy::factory()->create();
    $planned = Program::factory()->create(['name' => 'Planned Program', 'strategy_id' => $strategy->id]);
    $shell = Program::factory()->create(['name' => 'Shell Program', 'strategy_id' => $strategy->id]);
    $lastYear = Program::factory()->create(['name' => 'Last Year Program', 'strategy_id' => $strategy->id]);
    Program::factory()->create(['name' => 'Elsewhere Program', 'strategy_id' => Strategy::factory()->create()->id]);

    PlanIndicator::factory()->forAnnualPlan(AnnualPlan::factory()->create(['program_id' => $planned->id, 'year' => 2026]))->create();
    AnnualPlan::factory()->create(['program_id' => $shell->id, 'year' => 2026]);
    PlanIndicator::factory()->forAnnualPlan(AnnualPlan::factory()->create(['program_id' => $lastYear->id, 'year' => 2025]))->create();

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->set('strategy', (string) $strategy->id)
        ->set('gap', 'no_plan')
        ->assertSet('gap', 'no_plan')
        ->assertSee('Shell Program')
        ->assertSee('Last Year Program')
        ->assertDontSee('Planned Program')
        ->assertDontSee('Elsewhere Program');
});

test('a register narrowed to a gap names it in a chip with a labelled clear button', function () {
    $this->travelTo('2026-06-15 09:00:00');

    Livewire::withQueryParams(['gap' => 'no_plan'])
        ->actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->assertSeeHtml('data-gap-filter-chip')
        ->assertSeeText(__('programs.gap_no_plan', ['year' => 2026]))
        ->assertSeeText(__('programs.clear_gap'))
        ->set('gap', '')
        ->assertSet('gap', '')
        ->assertDontSeeHtml('data-gap-filter-chip');
});

test('each program has one status word and the three tab counts add up to the list', function () {
    $this->travelTo('2026-09-15 09:00:00');

    $late = Program::factory()->create(['name' => 'Late Program']);
    Project::factory()->create(['program_id' => $late->id, 'status' => ProjectStatus::Active, 'end_date' => '2026-08-01']);
    Program::factory()->unassigned()->create(['name' => 'Unlinked Program']);

    $page = Livewire::actingAs(User::factory()->role('General Manager')->create())->test('pages::programs.index');

    expect($page->instance()->focusCounts)->toMatchArray(['attention' => 1, 'setup' => 1, 'ontrack' => 0, 'total' => 2]);

    $page->assertSeeHtml('data-status="attention"')
        ->assertSeeHtml('data-status="setup"')
        ->assertSee('Needs attention')
        ->assertSee('Not set up yet');
});

test('the status buttons narrow the list and pressing the active one clears it', function () {
    $this->travelTo('2026-09-15 09:00:00');

    $late = Program::factory()->create(['name' => 'Late Program']);
    Project::factory()->create(['program_id' => $late->id, 'status' => ProjectStatus::Active, 'end_date' => '2026-08-01']);
    Program::factory()->unassigned()->create(['name' => 'Unlinked Program']);

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->call('setFocus', 'attention')
        ->assertSet('focus', 'attention')
        ->assertSee('Late Program')
        ->assertDontSee('Unlinked Program')
        ->call('setFocus', 'attention')
        ->assertSet('focus', '')
        ->assertSee('Unlinked Program')
        ->call('setFocus', 'nonsense')
        ->assertSet('focus', '');
});

test('late programs come first, then unfunded ones, then the ones still to set up', function () {
    $this->travelTo('2026-09-15 09:00:00');

    $fine = Program::factory()->create(['name' => 'Aaa Unfunded Program']);
    Project::factory()->create(['program_id' => $fine->id, 'status' => ProjectStatus::Closed, 'end_date' => '2026-12-01']);
    Program::factory()->unassigned()->create(['name' => 'Bbb Unlinked Program']);
    $late = Program::factory()->create(['name' => 'Zzz Late Program']);
    Project::factory()->create(['program_id' => $late->id, 'status' => ProjectStatus::Active, 'end_date' => '2026-08-01']);

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->assertSeeInOrder(['Zzz Late Program', 'Aaa Unfunded Program', 'Bbb Unlinked Program']);
});

test('the manager filter sits behind More filters and the controls stay inside the component', function () {
    Program::factory()->create(['name' => 'Water Security']);

    $html = Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('pages::programs.index')
        ->html();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
    $xpath = new DOMXPath($dom);
    $root = '//section[@*[name()="wire:name"]="pages::programs.index"]';

    foreach (['more-filters-button', 'focus-tab-attention', 'focus-tab-setup', 'focus-tab-ontrack'] as $control) {
        expect($xpath->query($root.'//*[@data-test="'.$control.'"]')->length)->toBeGreaterThan(0, "{$control} sits outside the component");
    }
    expect($xpath->query($root.'//ul[@data-test="program-list"]//li')->length)->toBeGreaterThan(0);
});
