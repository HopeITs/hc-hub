<?php

use Carbon\CarbonImmutable;
use Modules\Hr\Models\AttendanceDevice;
use Modules\Hr\Models\AttendanceDeviceSighting;
use Modules\Hr\Models\AttendanceImport;
use Modules\Hr\Models\AttendanceMonth;
use Modules\Hr\Models\AttendancePunch;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Attendance\AttendanceImporter;

/*
| A ZKTeco device whose Cloud Server is set to ADMS calls /iclock/...: it says hello with its serial,
| is told where to start, and posts its attendance log. It has no login, so it is believed by its
| registered serial number only; a stranger is refused and remembered so HR can add it.
*/

beforeEach(function () {
    $this->device = fn (array $attributes = []): AttendanceDevice => AttendanceDevice::query()->create($attributes + ['serial' => 'SFAA123456', 'name' => 'Hope Center']);

    $this->push = fn (string $serial, string $body, ?string $stamp = null) => $this->call(
        'POST',
        '/iclock/cdata?SN='.$serial.'&table=ATTLOG'.($stamp ? '&Stamp='.$stamp : ''),
        [], [], [], ['CONTENT_TYPE' => 'text/plain'], $body,
    );
});

test('a device that is not registered is refused and remembered', function () {
    $this->get('/iclock/cdata?SN=NEWDEVICE01&options=all')->assertForbidden()->assertSee('UNREGISTERED');
    $this->get('/iclock/getrequest?SN=NEWDEVICE01')->assertForbidden();

    expect(AttendanceDeviceSighting::query()->where('serial', 'NEWDEVICE01')->sole()->attempts)->toBe(2)
        // What it holds is not lost: nothing was taken in, so it will send it all again once added.
        ->and(AttendancePunch::query()->count())->toBe(0);

    // Not a serial at all: ignored, not even remembered.
    $this->get('/iclock/cdata?SN='.rawurlencode('x; drop table'))->assertForbidden();

    expect(AttendanceDeviceSighting::query()->count())->toBe(1);
});

test('hello from a registered device says where to start and moves on with its position', function () {
    $device = ($this->device)();

    $this->get('/iclock/cdata?SN=SFAA123456&options=all&pushver=2.4.1')
        ->assertOk()
        ->assertSee('GET OPTION FROM: SFAA123456', false)
        ->assertSee('ATTLOGStamp=None', false);

    $device->refresh();

    expect($device->last_seen_at)->not->toBeNull()
        ->and($device->push_version)->toBe('2.4.1');

    $this->get('/iclock/getrequest?SN=SFAA123456')->assertOk()->assertSee('OK');

    // After a push it reports where it got to; that is handed back at the next hello.
    ($this->push)('SFAA123456', '', '777')->assertOk();
    $this->get('/iclock/cdata?SN=SFAA123456')->assertSee('ATTLOGStamp=777', false);
});

test('the punches are taken in once and belong to the employee whose number it is', function () {
    $employee = Employee::factory()->create();
    $device = ($this->device)();
    $body = $employee->id."\t2026-09-14 07:58:10\t0\t1\t0\t0\t0\n"
        .$employee->id."\t2026-09-14 16:03:44\t1\t1\t0\t0\t0\n"
        ."99999\t2026-09-14 08:00:00\t0\t1\t0\t0\t0\n";

    ($this->push)('SFAA123456', $body, '3')->assertOk()->assertSee('OK:3', false);

    $mine = AttendancePunch::query()->where('device_no', (string) $employee->id)->get();

    expect(AttendancePunch::query()->count())->toBe(3)
        ->and($mine)->toHaveCount(2)
        ->and([$mine->first()->employee_id, $mine->first()->device_id])->toBe([$employee->id, $device->id])
        // A number that is nobody's yet is kept, waiting to be linked.
        ->and(AttendancePunch::query()->where('device_no', '99999')->value('employee_id'))->toBeNull();

    // The same log sent again (a retry, a second hello): still acknowledged, nothing doubled.
    ($this->push)('SFAA123456', $body, '3')->assertOk()->assertSee('OK:3', false);

    expect(AttendancePunch::query()->count())->toBe(3);

    $device->refresh();
    $import = AttendanceImport::query()->where('source', 'device')->sole();

    expect($device->last_punch_at->format('Y-m-d H:i:s'))->toBe('2026-09-14 16:03:44')
        ->and($device->attlog_stamp)->toBe('3')
        // One import row for the device's day, not one per push.
        ->and([$import->device_id, $import->punches_total, $import->punches_new])->toBe([$device->id, 6, 3]);
});

test('a locked month takes no new punches from a device', function () {
    $employee = Employee::factory()->create();
    ($this->device)();
    AttendanceMonth::query()->forceCreate(['employee_id' => $employee->id, 'month' => '2026-09-01', 'locked_at' => now()]);

    ($this->push)('SFAA123456', $employee->id."\t2026-09-14 07:58:10\t0\t1\n".$employee->id."\t2026-10-02 08:01:00\t0\t1\n")
        ->assertOk()->assertSee('OK:2', false);

    expect(AttendancePunch::query()->pluck('punched_at')->map->format('Y-m-d H:i:s')->all())->toBe(['2026-10-02 08:01:00']);
});

test('a blocked device is refused and a device call carries no session', function () {
    ($this->device)(['status' => AttendanceDevice::BLOCKED]);

    ($this->push)('SFAA123456', "1\t2026-09-14 07:58:10\t0\t1\n")->assertForbidden();

    expect(AttendancePunch::query()->count())->toBe(0);

    ($this->device)(['serial' => 'GOODONE001']);
    $response = $this->get('/iclock/getrequest?SN=GOODONE001')->assertOk();

    // No cookie, no session: a device is not a browser.
    expect($response->headers->has('Set-Cookie'))->toBeFalse();
});

test('a device goes from online to late to silent', function () {
    $device = ($this->device)();

    expect($device->health())->toBe('never');

    $device->forceFill(['last_seen_at' => now()->subMinutes(2)])->save();
    expect($device->fresh()->health())->toBe('online');

    $device->forceFill(['last_seen_at' => now()->subHours(3)])->save();
    expect($device->fresh()->health())->toBe('late');

    $device->forceFill(['last_seen_at' => now()->subDays(4)])->save();
    expect($device->fresh()->health())->toBe('silent');
});

test('the upload path still stores a file once through the shared routine', function () {
    $employee = Employee::factory()->create();
    $import = AttendanceImport::query()->create(['file_name' => 'report.csv']);
    $punches = [['device_no' => (string) $employee->id, 'at' => new CarbonImmutable('2026-09-14 08:00:00')]];

    $first = app(AttendanceImporter::class)->store($punches, $import);
    $again = app(AttendanceImporter::class)->store($punches, $import);

    expect($first)->toBe(['new' => 1, 'locked' => 0])
        ->and($again)->toBe(['new' => 0, 'locked' => 0]);
});
