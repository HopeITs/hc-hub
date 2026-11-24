<?php

use Modules\Hr\Models\AttendanceDevice;
use Modules\Hr\Models\AttendanceImport;
use Modules\Hr\Models\AttendancePunch;

/*
| attendance:purge-uploaded removes the punches that came from an uploaded report file (and the
| records of those uploads) so a device or the LAN agent can store its own copy; it counts first.
*/

beforeEach(function () {
    $device = AttendanceDevice::query()->create(['serial' => 'SFAA254800375', 'name' => 'X']);
    $file = AttendanceImport::query()->forceCreate(['file_name' => 'a.xls', 'source' => 'file', 'punches_total' => 1, 'punches_new' => 1]);
    $push = AttendanceImport::query()->forceCreate(['file_name' => 'dev', 'source' => 'agent', 'device_id' => $device->id, 'punches_total' => 1, 'punches_new' => 1]);
    AttendancePunch::query()->create(['import_id' => $file->id, 'device_no' => '1', 'punched_at' => '2026-09-14 08:00:00']);
    AttendancePunch::query()->create(['import_id' => $push->id, 'device_id' => $device->id, 'device_no' => '1', 'punched_at' => '2026-09-15 08:00:00']);
});

test('a dry run deletes nothing', function () {
    $this->artisan('attendance:purge-uploaded --dry-run')->assertSuccessful();

    expect(AttendancePunch::query()->count())->toBe(2);
});

test('it removes only uploaded punches and their records', function () {
    $this->artisan('attendance:purge-uploaded --force')->assertSuccessful();

    expect(AttendancePunch::query()->count())->toBe(1)
        ->and(AttendancePunch::query()->value('device_id'))->not->toBeNull()
        ->and(AttendanceImport::query()->pluck('source')->all())->toBe(['agent']);
});
