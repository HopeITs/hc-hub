<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\HrTables;

test('the HR migrations create every HR-owned table without the hchr_ prefix', function () {
    foreach (HrTables::targets() as $table) {
        expect(Schema::hasTable($table))->toBeTrue("{$table} is missing");
    }

    expect(Schema::hasTable('hchr_employees'))->toBeFalse()
        ->and(Schema::hasColumns('users', ['email', 'password', 'last_seen_at', 'employee_id']))->toBeTrue()
        ->and(Schema::hasColumns('programs', ['name', 'code', 'slug', 'start_date', 'end_date', 'is_active']))->toBeTrue();
});

test('the HR tables nothing reads are dropped and left out of the import', function () {
    foreach (['push_subscriptions', 'notification_outbox', 'task_attempts'] as $table) {
        expect(Schema::hasTable($table))->toBeFalse("{$table} still exists")
            ->and(HrTables::OWNED)->not->toHaveKey($table);
    }
});

test('the unread HR preference tables are parked under zz_drop_ and left out of the import', function () {
    foreach (['assistant_conversations', 'dashboard_layouts', 'navigation_preferences', 'notification_preferences', 'saved_table_views'] as $table) {
        expect(Schema::hasTable($table))->toBeFalse("{$table} still exists")
            ->and(Schema::hasTable("zz_drop_{$table}"))->toBeTrue("zz_drop_{$table} is missing")
            ->and(HrTables::OWNED)->not->toHaveKey($table);
    }
});

test('the columns HR lists filter and sort by are indexed', function () {
    $leading = fn (string $table): array => collect(Schema::getIndexes($table))
        ->map(fn (array $index): string => $index['columns'][0])
        ->all();

    expect($leading('employees'))->toContain('is_active', 'first_name_en', 'last_name_en', 'city_id')
        ->and($leading('contracts'))->toContain('is_active', 'start_date', 'end_date', 'employee_id', 'position_id')
        ->and($leading('leave_requests'))->toContain('status', 'start_at', 'leave_balance_id')
        ->and($leading('positions'))->toContain('is_active', 'city_id', 'program_id')
        ->and($leading('cities'))->toContain('is_active')
        ->and($leading('centers'))->toContain('is_active', 'city_id');
});

test('employee scopes filter by activity, governorate and name in either language', function () {
    $aleppo = City::factory()->create();
    $rami = Employee::factory()->create(['first_name_en' => 'Rami', 'first_name_ar' => 'رامي', 'city_id' => $aleppo->id]);
    Employee::factory()->inactive()->create(['first_name_en' => 'Rami', 'city_id' => $aleppo->id]);
    Employee::factory()->create(['first_name_en' => 'Lina']);

    expect(Employee::query()->active()->forCity($aleppo->id)->search('رام')->pluck('id')->all())->toBe([$rami->id])
        ->and(Employee::query()->active()->search('  ')->count())->toBe(2);
});

test('position, contract and leave request scopes reach the governorate and the employee', function () {
    $city = City::factory()->create();
    $contract = Contract::factory()
        ->for(Employee::factory()->state(['city_id' => $city->id]))
        ->for(Position::factory()->state(['city_id' => $city->id]))
        ->create();
    Contract::factory()->create(['is_active' => false]);
    $cityId = $city->id;
    $request = LeaveRequest::factory()->for(LeaveBalance::factory()->for($contract))->create();

    expect(Position::query()->active()->forCity($cityId)->search($contract->position->title_en)->pluck('id')->all())->toBe([$contract->position_id])
        ->and(Contract::query()->active()->forCity($cityId)->pluck('id')->all())->toBe([$contract->id])
        ->and(Contract::query()->search($contract->employee->last_name_en)->pluck('id')->all())->toBe([$contract->id])
        ->and(LeaveRequest::query()->forCity($cityId)->search($contract->employee->first_name_en)->pluck('id')->all())->toBe([$request->id]);
});

test('city and center options are cached per language and refreshed when a row changes', function () {
    $city = City::factory()->create(['name_en' => 'Aleppo', 'name_ar' => 'حلب']);
    $center = Center::factory()->for($city)->create(['name_en' => 'Hope Center', 'name_ar' => 'مركز الرجاء']);

    expect(City::options())->toBe([$city->id => 'Aleppo'])
        ->and(Center::options($city->id))->toBe([$center->id => 'Hope Center']);

    App::setLocale('ar');
    expect(City::options())->toBe([$city->id => 'حلب'])
        ->and($center->fresh()?->name)->toBe('مركز الرجاء');

    $city->update(['is_active' => false]);
    expect(City::options())->toBe([]);
});
