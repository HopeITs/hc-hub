<?php

use Livewire\Livewire;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\AttendanceDevice;
use Modules\Hr\Models\AttendanceDeviceSighting;
use Spatie\Permission\Models\Permission;

/*
| The device list: HR adds a device that called by choosing its center, sees which devices went
| quiet and which centers have none, and a city HR only reaches its own centers.
*/

beforeEach(function () {
    // The page is hidden by default (config/attendance.php devices_page); these tests are about the page itself.
    config(['attendance.devices_page' => true]);

    foreach (['attendance.view-own', 'attendance.view', 'attendance.manage'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->city = City::factory()->create();

    $this->center = fn (City $city, string $name): Center => Center::factory()->create([
        'name_en' => $name, 'name_ar' => $name, 'city_id' => $city->id, 'is_active' => true,
    ]);

    $this->hr = function (City $city): User {
        $user = User::factory()->role('HR-'.strtoupper($city->slug))->create();
        $user->givePermissionTo(['attendance.view-own', 'attendance.view', 'attendance.manage']);

        return $user->refresh();
    };
});

test('the device page is hidden until it is turned on', function () {
    config(['attendance.devices_page' => false]);
    $hr = ($this->hr)($this->city);

    $this->actingAs($hr)->get(route('hr.attendance.devices'))->assertNotFound();
    $this->actingAs($hr)->get(route('hr.attendance.upload'))->assertOk()->assertDontSee(route('hr.attendance.devices'));
});

test('only those who manage attendance open the device list', function () {
    $employee = User::factory()->create();
    $employee->givePermissionTo('attendance.view-own');

    $this->actingAs($employee->refresh())->get(route('hr.attendance.devices'))->assertForbidden();
    $this->actingAs(($this->hr)($this->city))->get(route('hr.attendance.devices'))->assertOk();
});

test('a device that called is added by choosing its center', function () {
    $center = ($this->center)($this->city, 'Hope Center Aleppo');
    AttendanceDeviceSighting::query()->create(['serial' => 'SFAA123456', 'last_ip' => '5.6.7.8', 'attempts' => 3, 'last_seen_at' => now()]);

    $page = Livewire::actingAs(($this->hr)($this->city))->test('hr::attendance.devices')
        ->assertSeeHtml('data-device-sightings')
        ->assertSee('SFAA123456')
        // No center chosen: not added.
        ->call('register', 'SFAA123456')
        ->assertHasErrors('newCenter.SFAA123456');

    expect(AttendanceDevice::query()->count())->toBe(0);

    $page->set('newCenter.SFAA123456', (string) $center->id)
        ->set('newName.SFAA123456', 'Front door')
        ->call('register', 'SFAA123456')
        ->assertHasNoErrors();

    $device = AttendanceDevice::query()->sole();

    expect([$device->serial, $device->name, $device->center_id])->toBe(['SFAA123456', 'Front door', $center->id])
        ->and(AttendanceDeviceSighting::query()->count())->toBe(0);
});

test('the list shows the state of each device and the centers without one', function () {
    $withDevice = ($this->center)($this->city, 'Alpha Center');
    ($this->center)($this->city, 'Bravo Center');
    AttendanceDevice::query()->create(['serial' => 'QUIET00001', 'name' => 'Alpha clock', 'center_id' => $withDevice->id, 'last_seen_at' => now()->subDays(5)]);

    Livewire::actingAs(($this->hr)($this->city))->test('hr::attendance.devices')
        ->assertSeeHtml('data-health="silent"')
        ->assertSee('Alpha clock')
        ->assertSeeHtml('data-centers-without-device')
        ->assertSee('Bravo Center');
});

test('a device can be blocked, edited and removed', function () {
    $first = ($this->center)($this->city, 'Alpha Center');
    $second = ($this->center)($this->city, 'Bravo Center');
    $device = AttendanceDevice::query()->create(['serial' => 'DEV0000001', 'name' => 'Old', 'center_id' => $first->id]);
    $hr = ($this->hr)($this->city);

    Livewire::actingAs($hr)->test('hr::attendance.devices')
        ->call('toggleBlock', $device->id)
        ->call('edit', $device->id)
        ->set('editName', 'New name')
        ->set('editCenter', (string) $second->id)
        ->call('saveDevice')
        ->assertHasNoErrors();

    $device->refresh();

    expect([$device->status, $device->name, $device->center_id])->toBe([AttendanceDevice::BLOCKED, 'New name', $second->id]);

    Livewire::actingAs($hr)->test('hr::attendance.devices')->call('removeDevice', $device->id);

    expect(AttendanceDevice::query()->count())->toBe(0);
});

test('a city hr reaches only the centers of its own city', function () {
    $other = City::factory()->create();
    $alp = ($this->center)($other, 'Aleppo Center');
    $own = ($this->center)($this->city, 'Homs Center');
    $inOther = AttendanceDevice::query()->create(['serial' => 'ALEPPODEV1', 'name' => 'Aleppo clock', 'center_id' => $alp->id]);
    AttendanceDeviceSighting::query()->create(['serial' => 'NEWONE0001', 'last_seen_at' => now()]);
    $hr = ($this->hr)($this->city);

    $page = Livewire::actingAs($hr)->test('hr::attendance.devices')
        ->assertDontSee('Aleppo clock')
        ->assertDontSee('Aleppo Center')
        ->assertSee('Homs Center');

    // A center of another city cannot be chosen, and another city's device cannot be touched.
    $page->set('newCenter.NEWONE0001', (string) $alp->id)->call('register', 'NEWONE0001')->assertHasErrors('newCenter.NEWONE0001');

    expect(AttendanceDevice::query()->where('serial', 'NEWONE0001')->count())->toBe(0);

    Livewire::actingAs($hr)->test('hr::attendance.devices')->call('toggleBlock', $inOther->id)->assertForbidden();

    // A fresh page, for the center of its own city (a refused call ends the first one).
    Livewire::actingAs($hr)->test('hr::attendance.devices')
        ->set('newCenter.NEWONE0001', (string) $own->id)->call('register', 'NEWONE0001')->assertHasNoErrors();

    expect(AttendanceDevice::query()->where('serial', 'NEWONE0001')->value('center_id'))->toBe($own->id);
});
