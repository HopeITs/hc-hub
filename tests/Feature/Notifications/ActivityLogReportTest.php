<?php

use App\Models\Activity;
use App\Support\ActivityReport;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;

/*
| The usage report above the Activity Log: who uses the hub most, when and where,
| and who did nothing. The page and its export read the same figures.
*/

beforeEach(function () {
    $this->actOnce = function (User $user, string $at = 'now', string $module = 'tasks'): void {
        Activity::withoutRecording(fn () => Activity::query()->forceCreate([
            'actor_id' => $user->id, 'subject_type' => 'task', 'subject_id' => 1, 'module' => $module,
            'event' => 'task.created', 'changes' => [], 'properties' => [],
            'created_at' => $at, 'updated_at' => $at,
        ]));
    };
});

test('it ranks the most active people in the period and counts the rest', function () {
    $busy = userWithRole('Managers');
    $quiet = userWithRole('Managers');
    Activity::query()->delete();

    foreach (range(1, 3) as $_) {
        ($this->actOnce)($busy);
    }
    ($this->actOnce)($quiet);
    ($this->actOnce)($quiet, now()->subDays(40)->toDateTimeString());

    $report = (new ActivityReport(30))->build();

    expect($report['total'])->toBe(4)
        ->and($report['activeUsers'])->toBe(2)
        ->and($report['topUsers']->pluck('actions')->all())->toBe([3, 1])
        ->and((new ActivityReport(365))->build()['total'])->toBe(5)
        ->and(ActivityReport::normaliseDays(12))->toBe(30);
});

test('the trend and the hours are zero filled and add up', function () {
    $person = userWithRole('Managers');
    Activity::query()->delete();
    ($this->actOnce)($person);
    ($this->actOnce)($person);

    $report = (new ActivityReport(7))->build();

    expect($report['daily'])->toHaveCount(7)
        ->and($report['daily']->sum('actions'))->toBe(2)
        ->and($report['daily']->last()['actions'])->toBe(2)
        ->and($report['hours'])->toHaveCount(24)
        ->and($report['hours']->sum('actions'))->toBe(2);
});

test('activity is counted by the city of the person who acted, and people with none are listed', function () {
    $city = City::factory()->create();
    $worker = userWithRole('Managers', Employee::factory()->create(['city_id' => $city->id, 'is_active' => true])->id);
    $idle = userWithRole('Managers', Employee::factory()->create(['city_id' => $city->id, 'is_active' => true])->id);
    Activity::query()->delete();
    ($this->actOnce)($worker);
    ($this->actOnce)($worker);

    $report = (new ActivityReport(30))->build();

    expect($report['cities']->all())->toBe([['name' => $city->name, 'actions' => 2, 'users' => 1]])
        ->and($report['inactive']->pluck('name')->all())->toContain($idle->name)->not->toContain($worker->name);
});

test('the report opens on the page, follows the period and exports the same figures', function () {
    $admin = userWithRole('Super Admin');
    Activity::query()->delete();
    ($this->actOnce)($admin);

    Livewire::actingAs($admin)->test('pages::activity.index')
        ->assertDontSeeHtml('data-activity-report-days')
        ->call('toggleReport')
        ->assertSeeHtml('data-activity-report-days="30"')
        ->assertSee(__('activity.report.title'))
        ->call('setReportDays', 90)
        ->assertSet('reportDays', 90)
        ->call('setReportDays', 12)
        ->assertSet('reportDays', 30)
        ->call('exportReport')
        ->assertFileDownloaded('activity_report_'.now()->format('Y-m-d').'.csv');
});

test('someone without the activity log permission cannot export the report', function () {
    Livewire::actingAs(userWithRole('Media Team'))->test('pages::activity.index')->call('exportReport')->assertForbidden();
});
