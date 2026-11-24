<?php

use App\Support\Modules\ModuleRegistry;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\MailList;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Search\ContractSearchSource;
use Modules\Hr\Support\Search\MailListSearchSource;
use Modules\Hr\Support\Search\PositionSearchSource;
use Modules\Hr\Support\Search\RecruitmentRequestSearchSource;
use Spatie\Permission\Models\Permission;

/*
| Ask finds more HR records than people: positions, contracts, hiring
| requests and mail lists, each only for whoever may open the page that
| lists them, and through that page's own scope.
*/

/**
 * The rows Ask shows a viewer for one source: label => href.
 *
 * @return array<string, string>
 */
function askSource(User $user, string $source, string $query): array
{
    $results = Livewire::actingAs($user)->test('desk.ask')->set('query', $query)->instance()->results;
    $group = collect($results['groups'])->firstWhere('source', $source);

    return array_column($group['items'] ?? [], 'href', 'label');
}

/**
 * A signed-in user holding exactly these permissions (no role, so no city limit).
 */
function searchViewerWith(string ...$permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user->fresh();
}

test('the HR module registers every kind of record it lets Ask find', function () {
    $registered = array_map(get_class(...), app(ModuleRegistry::class)->searchSources());

    expect($registered)->toContain(PositionSearchSource::class, ContractSearchSource::class, MailListSearchSource::class, RecruitmentRequestSearchSource::class);
});

test('a position is found by title in either language, for whoever may view positions', function () {
    Position::factory()->create(['title_en' => 'Field Coordinator', 'title_ar' => 'منسق ميداني', 'is_active' => true, 'max_emp' => 2, 'exist_emp' => 1]);
    Position::factory()->create(['title_en' => 'Accountant', 'is_active' => true]);

    $hr = searchViewerWith('positions.view');

    expect(askSource($hr, 'positions', 'coordinator'))->toBe(['Field Coordinator' => route('hr.positions.team')])
        ->and(askSource($hr, 'positions', 'منسق'))->toBe(['Field Coordinator' => route('hr.positions.team')])
        ->and(askSource(userWithRole('Team Member'), 'positions', 'coordinator'))->toBe([]);
});

test('a contract is found by the person or its number, and shows only the person, position and end date', function () {
    $employee = Employee::query()->findOrFail(insertHrEmployee(null, 'Rana', 'Contractor'));
    $contract = Contract::factory()->for($employee)->create(['is_active' => true, 'type' => ContractTypeEnum::FullTime->value]);
    Contract::factory()->for(Employee::query()->findOrFail(insertHrEmployee(null, 'Omar', 'Other')))->create(['is_active' => true, 'type' => ContractTypeEnum::FullTime->value]);

    $hr = searchViewerWith('contracts.view');
    $href = route('hr.contracts.show', ['contract' => $contract->id]);

    expect(askSource($hr, 'contracts', 'rana'))->toBe(['Rana Contractor' => $href])
        ->and(array_column(app(ContractSearchSource::class)->search($hr, (string) $contract->id, 5), 'href', 'title'))->toBe(['Rana Contractor' => $href])
        ->and(askSource(userWithRole('Team Member'), 'contracts', 'rana'))->toBe([]);

    $rows = app(ContractSearchSource::class)->search($hr, 'rana', 5);

    expect(array_keys($rows[0]))->toBe(['title', 'context', 'href']);
});

test('a mail list is found by name or address by anyone signed in', function () {
    MailList::query()->create(['name' => 'Aleppo team', 'address' => 'hope.aleppo@hcsyria.org', 'position' => 1]);
    MailList::query()->create(['name' => 'Board', 'address' => 'board@hcsyria.org', 'position' => 2]);

    expect(askSource(userWithRole('Team Member'), 'mail-lists', 'aleppo'))->toBe(['Aleppo team' => route('hr.mail-lists.index')])
        ->and(askSource(userWithRole('Team Member'), 'mail-lists', 'board@'))->toBe(['Board' => route('hr.mail-lists.index')]);
});

test('a search with nothing typed, or only wildcards, finds nothing', function () {
    MailList::query()->create(['name' => 'Board', 'address' => 'board@hcsyria.org', 'position' => 1]);
    $user = userWithRole('HR-SYR');

    expect(app(MailListSearchSource::class)->search($user, '', 5))->toBe([])
        ->and(app(MailListSearchSource::class)->search($user, '%', 5))->toBe([])
        ->and(app(PositionSearchSource::class)->search($user, '_____', 5))->toBe([])
        ->and(app(RecruitmentRequestSearchSource::class)->search($user, '%', 5))->toBe([]);
});
