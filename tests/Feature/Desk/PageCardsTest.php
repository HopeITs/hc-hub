<?php

use App\Models\Account;
use App\Models\DonorProposal;
use App\Models\DonorReport;
use App\Models\EvidenceType;
use App\Models\Indicator;
use App\Models\LibraryIndicator;
use App\Models\NarrativeQuestion;
use App\Models\Report;
use App\Models\Result;
use App\Models\RiskLibrary;
use App\Models\Sector;
use App\Models\Strategy;
use App\Models\User;
use App\Support\Desk\PageCards;
use Livewire\Livewire;

/**
 * Every page with a card of its own (PageCards), kept in step by the test below it.
 *
 * @var list<string>
 */
const DeskPageCardRoutes = [
    'strategies.index', 'programs.index', 'annual-plans.index', 'results.index', 'indicator-library.index', 'indicators.index',
    'reports.index', 'donor-reports.index', 'funding-agreements.index', 'admin.accounts.index', 'admin.users.index', 'activity.index',
    'admin.cities.index', 'admin.sectors.index', 'admin.risk-library.index', 'admin.evidence-types.index', 'admin.narrative-questions.index',
];

beforeEach(function () {
    $this->travelTo('2026-09-15 09:00:00');
});

test('the card list here is the one PageCards holds', function () {
    expect(app(PageCards::class)->routes())->toBe(DeskPageCardRoutes);
});

test('every page card renders for a super admin, with its figures and rows', function (string $route) {
    Strategy::factory()->create();
    Sector::factory()->create();
    EvidenceType::factory()->create();
    NarrativeQuestion::factory()->create();
    RiskLibrary::factory()->create();
    Account::factory()->create();
    LibraryIndicator::factory()->create();
    Result::factory()->create();
    Indicator::factory()->create();
    Report::factory()->create();
    DonorReport::factory()->create();
    DonorProposal::factory()->create();

    Livewire::actingAs(User::factory()->role('Super Admin')->create())
        ->test('desk.panels.page', ['card' => PageCards::idFor($route), 'size' => 'L', 'gridCols' => 8, 'gridRows' => 5])
        ->assertOk()
        ->assertSeeHtml('data-card="'.$route.'"')
        ->assertSeeHtml('data-card-figures');
})->with(DeskPageCardRoutes);

test('a card lists its page\'s records, opening them, and its search narrows them in either language', function () {
    EvidenceType::factory()->create(['name_en' => 'Attendance sheet', 'name_ar' => 'كشف الحضور']);
    EvidenceType::factory()->create(['name_en' => 'Distribution photo', 'name_ar' => 'صورة التوزيع']);
    $meal = User::factory()->role('MEAL')->create();

    Livewire::actingAs($meal)
        ->test('desk.panels.page', ['card' => 'p-admin-evidence-types', 'size' => 'L', 'gridCols' => 6, 'gridRows' => 5])
        ->assertSee('Attendance sheet')
        ->assertSee('Distribution photo')
        ->assertSeeHtml('href="'.route('admin.evidence-types.index').'"')
        ->assertSeeHtml('data-card-create')
        ->set('search', 'attendance')
        ->assertSee('Attendance sheet')
        ->assertDontSee('Distribution photo')
        ->set('search', 'التوزيع')
        ->assertSee('Distribution photo')
        ->assertDontSee('Attendance sheet');
});

test('a card shows nothing of a page its viewer may not open', function () {
    Account::factory()->create(['account_name' => 'Operations reserve']);

    Livewire::actingAs(User::factory()->role('Team Member')->create())
        ->test('desk.panels.page', ['card' => 'p-admin-accounts', 'size' => 'L'])
        ->assertOk()
        ->assertDontSee('Operations reserve')
        ->assertSee(__('desk-workspace.card.unavailable'));
});

test('a card offers its page\'s "New …" only to someone who may create there', function () {
    $card = fn (User $user) => Livewire::actingAs($user)->test('desk.panels.page', ['card' => 'p-strategies', 'size' => 'L']);

    $card(User::factory()->role('Super Admin')->create())->assertSeeHtml('data-card-create');
    $card(User::factory()->role('Team Member')->create())->assertDontSeeHtml('data-card-create');
});

test('a donor report card shows a responsible person only the reports they answer for', function () {
    $mine = DonorReport::factory()->create(['title' => 'My quarterly report']);
    DonorReport::factory()->create(['title' => 'Someone else\'s report']);
    $employee = insertHrEmployee();
    $mine->forceFill(['responsible_employee_id' => $employee])->save();

    $user = User::factory()->role('Team Member')->create(['employee_id' => $employee]);
    $rows = app(PageCards::class)->for('donor-reports.index')->rows($user)->pluck('title')->all();

    expect($rows)->toBe(['My quarterly report']);
});
