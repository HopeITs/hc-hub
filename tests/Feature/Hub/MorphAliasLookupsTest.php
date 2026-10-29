<?php

/*
 * Every place that reads or writes a *_type column by hand, after the switch
 * to morph aliases. A wrong alias here would not throw: the query would just
 * match nothing, so each lookup is pinned by a test that needs a hit.
 */

use App\Livewire\ActivityLog\ActivityLogTable;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Forms\NewsForm;
use App\Models\Folder;
use App\Models\News;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesHrFixtures;

uses(CreatesHrFixtures::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // Media tests write real files. Each test gets its own disk roots so
    // parallel workers never share (or delete) each other's files.
    $this->diskRoot = storage_path('framework/testing/morph-'.Str::uuid());
    config([
        'filesystems.disks.local.root' => $this->diskRoot.'/private',
        'filesystems.disks.public.root' => $this->diskRoot.'/public',
    ]);
    Storage::forgetDisk(['local', 'public']);
});

afterEach(function () {
    File::deleteDirectory($this->diskRoot);
});

function makeNews(string $title, ?string $type, ?int $id, string $eventDate): News
{
    return News::query()->forceCreate([
        'title_en' => $title,
        'title_ar' => $title,
        'description_ar' => 'نص',
        'newsable_type' => $type,
        'newsable_id' => $id,
        'event_date' => $eventDate,
        'published_at' => now()->subWeek(),
    ]);
}

test('dashboard events include news targeted at the viewer\'s employee and city', function () {
    $city = $this->makeCity('ALP', '001');
    $otherCity = $this->makeCity('HMS', '002');
    $employee = $this->makeEmployee($city, 'viewer-'.Str::random(8));
    $other = $this->makeEmployee($otherCity, 'other-'.Str::random(8));
    $user = $this->makeUser($city, 'employee', [], $employee);

    $today = now()->toDateString();
    $tomorrow = now()->addDay()->toDateString();

    makeNews('Today for me', 'employee', $employee->id, $today);
    makeNews('Today for my city', 'city', $city->id, $today);
    makeNews('Today for someone else', 'employee', $other->id, $today);
    makeNews('Today for another city', 'city', $otherCity->id, $today);
    makeNews('Soon for me', 'employee', $employee->id, $tomorrow);
    makeNews('Soon for my city', 'city', $city->id, $tomorrow);
    makeNews('Soon for another city', 'city', $otherCity->id, $tomorrow);

    /** @var DashboardIndex $dashboard */
    $dashboard = Livewire::actingAs($user)->test(DashboardIndex::class)->instance();

    $todayTitles = $dashboard->todayEvents->where('event_type', 'news')->pluck('title_en')->sort()->values()->all();
    $upcomingTitles = $dashboard->upcomingEvents->pluck('title_en')->sort()->values()->all();

    expect($todayTitles)->toBe(['Today for me', 'Today for my city'])
        ->and($upcomingTitles)->toBe(['Soon for me', 'Soon for my city']);
});

test('the news form lists cities for the city alias and stores the alias', function () {
    $city = $this->makeCity('ALP', '001');
    $employee = $this->makeEmployee($city, 'news-'.Str::random(8));

    $form = new NewsForm(new class extends Component {}, 'form');
    $form->newsable_type = 'city';

    expect(NewsForm::allowedNewsableTypes())->toBe(['city', 'center', 'program', 'employee'])
        ->and($form->entities()->pluck('id')->all())->toBe([$city->id]);

    $form->newsable_type = 'employee';

    expect($form->entities()->pluck('id')->all())->toBe([$employee->id]);

    $news = makeNews('Targeted', 'city', $city->id, now()->toDateString());

    expect($news->fresh()->newsable->is($city))->toBeTrue()
        ->and($news->fresh()->newsable_name)->toContain($city->name);
});

test('the activity-log purge redacts rows whose subject type is the employee alias', function () {
    $city = $this->makeCity('ALP', '001');
    $employee = $this->makeEmployee($city, 'purge-'.Str::random(8));
    DB::table('activity_log')->delete();

    $id = DB::table('activity_log')->insertGetId([
        'log_name' => 'default',
        'description' => 'updated',
        'subject_type' => 'employee',
        'subject_id' => $employee->id,
        'properties' => json_encode([
            'old' => ['national_number' => '01234567890', 'first_name_en' => 'Old'],
            'attributes' => ['national_number' => '09876543210', 'first_name_en' => 'New'],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('security:purge-activity-log')->assertSuccessful();

    $properties = json_decode((string) DB::table('activity_log')->where('id', $id)->value('properties'), true);

    expect($properties['old']['national_number'])->toBe('[redacted]')
        ->and($properties['attributes']['national_number'])->toBe('[redacted]')
        ->and($properties['attributes']['first_name_en'])->toBe('New');
});

test('archive file routes serve folder media and 404 any other owner', function () {
    $city = $this->makeCity('ALP', '001');
    $admin = $this->makeUser($city, 'super-admin', ['archives.view', 'archives.download']);
    $employee = $this->makeEmployee($city, 'archive-'.Str::random(8));
    $folder = Folder::query()->create(['name' => 'Board', 'user_id' => $admin->id, 'is_shared' => false]);

    $folderMedia = $folder->addMediaFromString('%PDF-1.4 folder')
        ->usingFileName('minutes.pdf')
        ->toMediaCollection('archived_files', 'local');

    $employeeMedia = $employee->addMediaFromString('%PDF-1.4 employee')
        ->usingFileName('cv.pdf')
        ->toMediaCollection('attachments', 'local');

    expect($folderMedia->model_type)->toBe('folder')
        ->and($employeeMedia->model_type)->toBe('employee');

    $this->actingAs($admin)
        ->get(route('folder-files.preview', $folderMedia))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('folder-files.preview', $employeeMedia))
        ->assertNotFound();
});

test('the private-disk media migration picks up employee alias rows only', function () {
    $city = $this->makeCity('ALP', '001');
    $employee = $this->makeEmployee($city, 'private-'.Str::random(8));
    $folder = Folder::query()->create(['name' => 'Public', 'user_id' => null, 'is_shared' => true]);

    $attachment = $employee->addMediaFromString('%PDF-1.4 employee')
        ->usingFileName('id-card.pdf')
        ->toMediaCollection('attachments', 'public');

    $folderFile = $folder->addMediaFromString('%PDF-1.4 folder')
        ->usingFileName('flyer.pdf')
        ->toMediaCollection('attachments', 'public');

    expect($attachment->model_type)->toBe('employee');

    $this->artisan('security:migrate-employee-media')
        ->expectsOutput('Migrating 1 employee document(s) to private storage.')
        ->assertSuccessful();

    expect($attachment->fresh()->disk)->toBe('local')
        ->and(Storage::disk('local')->exists($attachment->fresh()->getPathRelativeToRoot()))->toBeTrue()
        ->and($folderFile->fresh()->disk)->toBe('public');
});

test('the activity log Model column searches by class name and tolerates a stale class filter', function () {
    $city = $this->makeCity('ALP', '001');
    $admin = $this->makeUser($city, 'super-admin');
    DB::table('activity_log')->delete();

    foreach (['leave_request' => 'leave row', 'employee' => 'employee row'] as $type => $description) {
        DB::table('activity_log')->insert([
            'log_name' => 'default',
            'description' => $description,
            'subject_type' => $type,
            'subject_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Substring match, like the column's own LIKE search.
    expect(ActivityLogTable::subjectAliasesMatching('LeaveRequest'))->toBe(['leave_request', 'leave_request_note'])
        ->and(ActivityLogTable::subjectAliasesMatching('App\\Models\\Employee'))->toBe(['employee', 'employee_item', 'employee_note'])
        ->and(ActivityLogTable::subjectAliasesMatching('  '))->toBe([])
        ->and(ActivityLogTable::subjectTypeAlias('App\\Models\\LeaveRequest'))->toBe('leave_request')
        ->and(ActivityLogTable::subjectTypeAlias('leave_request'))->toBe('leave_request');

    Livewire::actingAs($admin)
        ->test(ActivityLogTable::class)
        ->set('search.subject_type', 'LeaveRequest')
        ->assertSee('leave row')
        ->assertSee('LeaveRequest')
        ->assertDontSee('employee row')
        ->set('search.subject_type', '')
        ->set('filters.subject_type', 'App\\Models\\Employee')
        ->assertSee('employee row')
        ->assertDontSee('leave row')
        ->set('filters.subject_type', 'employee')
        ->assertSee('employee row')
        ->assertDontSee('leave row');
});

test('User alias is used for notifications sent to a user', function () {
    $user = User::factory()->create();

    $user->notify(new class extends Illuminate\Notifications\Notification
    {
        public function via(object $notifiable): array
        {
            return ['database'];
        }

        public function toArray(object $notifiable): array
        {
            return ['message' => 'hi'];
        }
    });

    expect(DB::table('notifications')->value('notifiable_type'))->toBe('user')
        ->and($user->fresh()->notifications)->toHaveCount(1);
});
