<?php

use App\Models\Permission;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeavingCategory;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\TerminationReason;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Reports\LeavingReasonsReport;
use Modules\Hr\Support\Settings\HrAccess;

/*
| Why people leave: reasons recorded on the contracts of people who left.
| One row per person (their latest contract), scoped to the viewer's city,
| with filters, turnover, the previous period and a monthly reminder to HR.
| "Today" is 2026-11-23.
*/

beforeEach(function () {
    Access::flush();
    HrAccess::flush();
    $this->travelTo('2026-11-23 10:00:00');
    Notification::fake();

    $this->alp = City::factory()->create(['slug' => 'ALP', 'code' => '001', 'name_en' => 'Aleppo', 'name_ar' => 'حلب']);
    $this->hms = City::factory()->create(['slug' => 'HMS', 'code' => '002', 'name_en' => 'Homs', 'name_ar' => 'حمص']);
    $this->type = ContractType::factory()->create();
    $this->position = Position::factory()->create(['city_id' => $this->alp->id, 'max_emp' => 50, 'exist_emp' => 0]);
    $this->resigned = TerminationReason::query()->create(['title_en' => 'Resigned', 'title_ar' => 'استقالة']);
    $this->relocated = TerminationReason::query()->create(['title_en' => 'Relocated', 'title_ar' => 'انتقال']);

    $this->contract = fn (Employee $employee, array $attributes = []): Contract => Contract::factory()->for($employee)->create($attributes + [
        'position_id' => $this->position->id,
        'contract_type_id' => $this->type->id,
        'is_active' => true,
    ]);

    $this->left = fn (City $city, string $on, ?TerminationReason $reason, string $from = '2025-01-01'): Contract => ($this->contract)(
        Employee::factory()->create(['city_id' => $city->id]),
        ['start_date' => $from, 'end_date' => $on, 'terminated_at' => $on, 'termination_reason_id' => $reason?->id, 'is_active' => false],
    );

    $this->viewer = fn (string $role, string ...$permissions): User => tap(User::factory()->role($role)->create(), function (User $user) use ($permissions): void {
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    })->fresh();

    $this->headOffice = fn (): User => ($this->viewer)('Super Admin');
    $this->report = fn (User $viewer, int $days = 365, array $filters = []): array => (new LeavingReasonsReport($viewer, $days, $filters))->build();
});

test('it counts one row per person with the reasons, cities and the gaps', function () {
    ($this->left)($this->alp, '2026-10-01', $this->resigned);
    ($this->left)($this->alp, '2026-10-15', $this->resigned);
    ($this->left)($this->hms, '2026-09-10', $this->relocated);
    ($this->left)($this->hms, '2026-09-20', null);
    ($this->left)($this->alp, '2024-01-01', $this->resigned);
    ($this->contract)(Employee::factory()->create(['city_id' => $this->alp->id]), ['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);

    $r = ($this->report)(($this->headOffice)());

    expect($r['total'])->toBe(4)
        ->and($r['recorded'])->toBe(3)
        ->and($r['missing'])->toBe(1)
        ->and($r['byReason']->pluck('count', 'label')->all())->toBe(['Resigned' => 2, 'Relocated' => 1])
        ->and($r['byCity']->pluck('count', 'label')->sort()->all())->toBe(['Aleppo' => 2, 'Homs' => 2])
        ->and($r['monthly']->all())->toBe(['2026-09' => 2, '2026-10' => 2])
        ->and($r['withoutReason'])->toHaveCount(1)
        ->and(($this->report)(($this->headOffice)(), 0)['total'])->toBe(5);
});

test('someone who left and came back is not counted as leaving', function () {
    $employee = Employee::factory()->create(['city_id' => $this->alp->id]);
    ($this->contract)($employee, ['start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'terminated_at' => '2026-06-30', 'termination_reason_id' => $this->resigned->id, 'is_active' => false]);
    ($this->contract)($employee, ['start_date' => '2026-09-01', 'end_date' => '2027-03-01']);

    expect(($this->report)(($this->headOffice)())['total'])->toBe(0);
});

test('a city HR sees only their own city', function () {
    ($this->left)($this->alp, '2026-10-01', $this->resigned);
    ($this->left)($this->hms, '2026-09-10', $this->relocated);

    $r = ($this->report)(($this->viewer)('HR-ALP', 'contracts.view'));

    expect($r['total'])->toBe(1)
        ->and($r['byCity']->pluck('label')->all())->toBe(['Aleppo']);
});

test('only HR and head office open the page', function () {
    ($this->left)($this->alp, '2026-10-01', $this->resigned);

    Livewire::actingAs(($this->headOffice)())->test('hr::reports.leaving-reasons')->assertOk()->assertSee('Resigned');
    Livewire::actingAs(($this->viewer)('HR-ALP', 'contracts.view'))->test('hr::reports.leaving-reasons')->assertOk();

    foreach (['manager', 'employee'] as $role) {
        Livewire::actingAs(($this->viewer)($role, 'contracts.view'))->test('hr::reports.leaving-reasons')->assertForbidden();
    }

    $this->actingAs(($this->viewer)('manager', 'contracts.view'))->get(route('hr.reports.leaving-reasons'))->assertForbidden();
});

test('leavers group by category with the unclassified and the missing apart', function () {
    $this->resigned->update(['category' => LeavingCategory::Voluntary]);

    ($this->left)($this->alp, '2026-10-01', $this->resigned);
    ($this->left)($this->alp, '2026-10-02', $this->resigned);
    ($this->left)($this->alp, '2026-10-03', $this->relocated);
    ($this->left)($this->alp, '2026-10-04', null);

    expect(($this->report)(($this->headOffice)())['byCategory']->pluck('count', 'key')->all())
        ->toBe(['voluntary' => 2, 'unclassified' => 1, 'missing' => 1]);
});

test('time with us is split into bands', function () {
    ($this->left)($this->alp, '2026-10-01', $this->resigned, '2026-09-01');
    ($this->left)($this->alp, '2026-10-01', $this->resigned, '2026-05-01');
    ($this->left)($this->alp, '2026-10-01', $this->resigned, '2024-01-01');

    expect(($this->report)(($this->headOffice)())['byTenure']->pluck('count')->all())->toBe([1, 1, 0, 1]);
});

test('filters narrow the leavers by position attributes and contract type', function () {
    $otherDepartment = Department::factory()->create();
    $otherType = ContractType::factory()->create();

    ($this->left)($this->alp, '2026-10-01', $this->resigned);
    $accountant = ($this->left)($this->alp, '2026-10-02', $this->resigned);
    $other = Position::factory()->create(['city_id' => $this->alp->id, 'department_id' => $otherDepartment->id]);
    $accountant->forceFill(['position_id' => $other->id, 'contract_type_id' => $otherType->id])->save();

    $total = fn (array $filters): int => ($this->report)(($this->headOffice)(), 365, $filters)['total'];

    expect($total([]))->toBe(2)
        ->and($total(['department_id' => $otherDepartment->id]))->toBe(1)
        ->and($total(['position_id' => $this->position->id]))->toBe(1)
        ->and($total(['contract_type_id' => $otherType->id]))->toBe(1)
        ->and($total(['department_id' => $otherDepartment->id, 'contract_type_id' => $this->type->id]))->toBe(0)
        ->and($total(['department_id' => '']))->toBe(2);
});

test('turnover and the previous period', function () {
    ($this->left)($this->alp, '2026-10-01', $this->resigned);
    ($this->left)($this->alp, '2026-10-02', $this->resigned);
    ($this->left)($this->alp, '2025-06-01', $this->resigned);

    foreach (['s1', 's2'] as $ignored) {
        ($this->contract)(Employee::factory()->create(['city_id' => $this->alp->id]), ['start_date' => '2025-01-01', 'end_date' => '2027-01-01']);
    }

    $r = ($this->report)(($this->headOffice)());

    expect($r['total'])->toBe(2)
        ->and($r['previousTotal'])->toBe(1)
        ->and($r['headcount'])->toBe(3)
        ->and($r['turnoverPercent'])->toBe(66.7);

    $all = ($this->report)(($this->headOffice)(), 0);

    expect($all['previousTotal'])->toBeNull()
        ->and($all['turnoverPercent'])->toBeNull();
});

test('the page links the gaps to the contract only for those who may edit it', function () {
    $gap = ($this->left)($this->alp, '2026-10-01', null);
    $link = route('hr.contracts.edit', $gap->id);

    Livewire::actingAs(($this->viewer)('HR-ALP', 'contracts.view'))->test('hr::reports.leaving-reasons')->assertDontSee($link, false);
    Livewire::actingAs(($this->viewer)('HR-ALP', 'contracts.view', 'contracts.update'))->test('hr::reports.leaving-reasons')->assertSee($link, false);
});

test('the page filters, shows the comparison and exports the same figures', function () {
    ($this->left)($this->alp, '2026-10-01', $this->resigned);

    Livewire::actingAs(($this->headOffice)())
        ->test('hr::reports.leaving-reasons')
        ->assertSeeHtml('data-testid="leaving-delta"')
        ->set('filters.department_id', 999999)
        ->assertSee(__('hr_reports.leaving.nobody'))
        ->call('clearFilters')
        ->assertDontSee(__('hr_reports.leaving.nobody'))
        ->call('export')
        ->assertFileDownloaded('leaving-reasons-2026-11-23.csv');
});

test('HR is reminded once a month about leavers with no reason', function () {
    ($this->left)($this->alp, now()->subDays(40)->toDateString(), null);
    ($this->left)($this->alp, now()->subDays(5)->toDateString(), null);
    ($this->left)($this->alp, now()->subDays(40)->toDateString(), $this->resigned);
    ($this->left)($this->alp, now()->subDays(500)->toDateString(), null);
    $hr = ($this->viewer)('HR-ALP', 'contracts.view');

    $this->artisan('leavers:remind-missing-reasons')->assertSuccessful();
    $this->artisan('leavers:remind-missing-reasons')->assertSuccessful();

    Notification::assertSentToTimes($hr, JourneyNotice::class, 1);
    Notification::assertSentTo($hr, JourneyNotice::class, fn (JourneyNotice $notice): bool => $notice->kind === 'leaving-reasons-missing' && $notice->params['count'] === 1);
});

test('a termination reason can be classified in HR settings', function () {
    $admin = ($this->headOffice)();

    Livewire::actingAs($admin)->test('hr::settings.reference-table', ['table' => 'termination-reasons'])
        ->call('openCreateModal')
        ->set('form.title_en', 'Moved abroad')
        ->set('form.title_ar', 'سفر')
        ->set('form.category', 'voluntary')
        ->call('save')
        ->assertHasNoErrors();

    expect(TerminationReason::query()->where('title_en', 'Moved abroad')->firstOrFail()->category)->toBe(LeavingCategory::Voluntary);

    Livewire::actingAs($admin)->test('hr::settings.reference-table', ['table' => 'termination-reasons'])
        ->call('openCreateModal')
        ->set('form.title_en', 'X')
        ->set('form.title_ar', 'س')
        ->set('form.category', 'nonsense')
        ->call('save')
        ->assertHasErrors('form.category');
});
