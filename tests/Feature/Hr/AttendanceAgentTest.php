<?php

use Livewire\Livewire;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\AttendanceDevice;
use Modules\Hr\Models\AttendanceImport;
use Modules\Hr\Models\AttendanceMonth;
use Modules\Hr\Models\AttendancePunch;
use Modules\Hr\Models\Employee;
use Spatie\Permission\Models\Permission;

/*
| The attendance agent reads a device on its own network and sends the punches here with the device's
| sync key. The key is the device: it is shown once, only its hash is kept, and a batch is taken in by
| the same routine as every other way in. A device that never calls is added by its serial.
*/

beforeEach(function () {
    $this->device = function (array $attributes = []): array {
        $device = AttendanceDevice::query()->create($attributes + ['serial' => 'SFAA254800375', 'name' => 'Science Zone']);

        return [$device, $device->issueAgentKey()];
    };

    $this->send = fn (string $key, array $punches, array $extra = []) => $this->postJson(
        '/api/attendance/agent/punches',
        ['punches' => $punches] + $extra,
        ['Authorization' => 'Bearer '.$key],
    );

    $this->hr = function (City $city): User {
        foreach (['attendance.view-own', 'attendance.view', 'attendance.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->role('HR-'.strtoupper($city->slug))->create();
        $user->givePermissionTo(['attendance.view-own', 'attendance.view', 'attendance.manage']);

        return $user->refresh();
    };
});

test('without a valid key nothing is taken', function () {
    [$device] = ($this->device)();

    $this->getJson('/api/attendance/agent/state')->assertUnauthorized();
    $this->getJson('/api/attendance/agent/state', ['Authorization' => 'Bearer nonsense'])->assertUnauthorized();
    $this->getJson('/api/attendance/agent/state', ['Authorization' => 'Bearer zk_'.str_repeat('a', 40)])->assertUnauthorized();
    $this->postJson('/api/attendance/agent/punches', ['punches' => []])->assertUnauthorized();

    expect(AttendancePunch::query()->count())->toBe(0);

    // A blocked device is refused even with its own key.
    $device->update(['status' => AttendanceDevice::BLOCKED]);
    $this->getJson('/api/attendance/agent/state', ['Authorization' => 'Bearer '.$device->issueAgentKey()])->assertForbidden();
});

test('the key is kept only as a hash and a new one replaces the old', function () {
    [$device, $key] = ($this->device)();

    expect($key)->toStartWith('zk_');
    $device->refresh();

    expect($device->agent_key_hash)->toBe(hash('sha256', $key))
        ->and($device->agent_key_hint)->toBe(substr($key, -4))
        ->and(json_encode($device->getAttributes()))->not->toContain($key);

    $this->getJson('/api/attendance/agent/state', ['Authorization' => 'Bearer '.$key])->assertOk();

    $new = $device->issueAgentKey();
    $this->getJson('/api/attendance/agent/state', ['Authorization' => 'Bearer '.$key])->assertUnauthorized();
    $this->getJson('/api/attendance/agent/state', ['Authorization' => 'Bearer '.$new])->assertOk();

    $device->revokeAgentKey();
    $this->getJson('/api/attendance/agent/state', ['Authorization' => 'Bearer '.$new])->assertUnauthorized();

    expect($device->fresh()->hasAgentKey())->toBeFalse();
});

test('punches are taken in once and the state says where they reach', function () {
    $employee = Employee::factory()->create();
    [$device, $key] = ($this->device)();

    $this->getJson('/api/attendance/agent/state', ['Authorization' => 'Bearer '.$key])
        ->assertOk()->assertJson(['serial' => 'SFAA254800375', 'last_punch_at' => null]);

    $punches = [
        ['pin' => (string) $employee->id, 'at' => '2026-09-14 07:58:10'],
        ['pin' => (string) $employee->id, 'at' => '2026-09-14 16:03:44'],
        ['pin' => '99999', 'at' => '2026-09-14 08:00:00'],
    ];

    ($this->send)($key, $punches, ['serial' => 'SFAA254800375'])
        ->assertOk()->assertJson(['received' => 3, 'new' => 3, 'locked' => 0, 'rejected' => 0, 'last_punch_at' => '2026-09-14 16:03:44']);

    $mine = AttendancePunch::query()->where('device_no', (string) $employee->id)->get();

    expect($mine)->toHaveCount(2)
        ->and([$mine->first()->employee_id, $mine->first()->device_id])->toBe([$employee->id, $device->id])
        ->and(AttendancePunch::query()->where('device_no', '99999')->value('employee_id'))->toBeNull();

    // The same batch again (a retry, or the next run reading the same log): nothing doubled.
    ($this->send)($key, $punches)->assertOk()->assertJson(['received' => 3, 'new' => 0]);

    expect(AttendancePunch::query()->count())->toBe(3)
        ->and(AttendanceImport::query()->where('source', 'agent')->count())->toBe(1)
        ->and($device->fresh()->agent_last_sync_at)->not->toBeNull();

    $this->getJson('/api/attendance/agent/state', ['Authorization' => 'Bearer '.$key])->assertJson(['last_punch_at' => '2026-09-14 16:03:44']);
});

test('a locked month, a wrong clock and a bad batch are handled', function () {
    $employee = Employee::factory()->create();
    [, $key] = ($this->device)();
    AttendanceMonth::query()->forceCreate(['employee_id' => $employee->id, 'month' => '2026-09-01', 'locked_at' => now()]);

    ($this->send)($key, [
        ['pin' => (string) $employee->id, 'at' => '2026-09-14 07:58:10'],   // a locked month
        ['pin' => (string) $employee->id, 'at' => '2026-10-02 08:01:00'],   // fine
        ['pin' => (string) $employee->id, 'at' => '2000-02-08 01:34:15'],   // a clock that was never set
        ['pin' => (string) $employee->id, 'at' => now()->addDays(40)->format('Y-m-d H:i:s')], // far ahead
    ])->assertOk()->assertJson(['new' => 1, 'locked' => 1, 'rejected' => 2]);

    expect(AttendancePunch::query()->pluck('punched_at')->map->format('Y-m-d H:i:s')->all())->toBe(['2026-10-02 08:01:00']);

    ($this->send)($key, [['pin' => '1', 'at' => 'yesterday']])->assertStatus(422)->assertJson(['error' => 'invalid_request']);
    ($this->send)($key, array_fill(0, 2001, ['pin' => '1', 'at' => '2026-10-02 08:01:00']))->assertStatus(422);
    ($this->send)($key, [], ['serial' => 'SOMEONEELSE1'])->assertStatus(422)->assertJson(['error' => 'serial_mismatch']);
});

test('HR makes a key shown once and a city HR reaches only its own centers', function () {
    $aleppo = City::factory()->create();
    $homs = City::factory()->create();
    $center = Center::factory()->create(['city_id' => $aleppo->id, 'is_active' => true]);
    $device = AttendanceDevice::query()->create(['serial' => 'SFAA254800375', 'name' => 'Science Zone', 'center_id' => $center->id]);
    $hr = ($this->hr)($aleppo);
    $homsHr = ($this->hr)($homs);

    $page = Livewire::actingAs($hr)->test('hr::attendance.devices')->call('createKey', $device->id);
    $key = $page->get('newKey');

    expect($key)->toStartWith('zk_')
        ->and(AttendanceDevice::forAgentKey($key)?->is($device))->toBeTrue();

    $page->assertSee($key)->assertSeeHtml('data-new-key');

    // Shown once: after "Done" the page no longer has it, and the database never did.
    $page->call('hideKey')->assertDontSee($key);
    Livewire::actingAs($hr)->test('hr::attendance.devices')->assertDontSee($key)->assertSee('zk_••••'.substr($key, -4));

    // A city HR of another city cannot make or revoke a key for this device.
    Livewire::actingAs($homsHr)->test('hr::attendance.devices')->call('createKey', $device->id)->assertForbidden();
    Livewire::actingAs($homsHr)->test('hr::attendance.devices')->call('revokeKey', $device->id)->assertForbidden();

    expect($device->fresh()->hasAgentKey())->toBeTrue();

    Livewire::actingAs($hr)->test('hr::attendance.devices')->call('revokeKey', $device->id);

    expect($device->fresh()->hasAgentKey())->toBeFalse();
});

test('a device that never calls is added by its serial', function () {
    $aleppo = City::factory()->create();
    $center = Center::factory()->create(['city_id' => $aleppo->id, 'is_active' => true]);

    Livewire::actingAs(($this->hr)($aleppo))->test('hr::attendance.devices')
        ->set('addSerial', 'bad serial!')->set('addCenter', (string) $center->id)->call('addBySerial')->assertHasErrors('addSerial')
        ->set('addSerial', ' sfaa254800375 ')->set('addCenter', '')->call('addBySerial')->assertHasErrors('addCenter')
        ->set('addCenter', (string) $center->id)->call('addBySerial')->assertHasNoErrors();

    $device = AttendanceDevice::query()->where('serial', 'SFAA254800375')->first();

    expect($device->center_id)->toBe($center->id)
        ->and(AttendanceDevice::query()->count())->toBe(1);
});
