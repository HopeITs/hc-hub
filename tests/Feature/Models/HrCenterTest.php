<?php

use Modules\Core\Models\Center as HrCenter;
use Modules\Hr\Models\JobLevel as HrJobLevel;
use Modules\Hr\Models\Position as HrPosition;

test('HR centers and job levels refuse to be saved or deleted', function (string $model, Closure $insert) {
    $record = $model::query()->findOrFail($insert());

    expect(fn () => $record->save())->toThrow(LogicException::class)
        ->and(fn () => $record->delete())->toThrow(LogicException::class);
})->skip('Obsolete since one database (A2): centers and job levels are ordinary writable Core/Hr models; the read-only guard is gone.')->with([
    'center' => [HrCenter::class, fn () => insertHrCenter(insertHrCity())],
    'job level' => [HrJobLevel::class, fn () => insertHrJobLevel()],
]);

test('center options list active centers only, in the interface language order', function () {
    $cityId = insertHrCity();
    $zed = insertHrCenter($cityId, 'Zed Center', 'مركز أ');
    $amy = insertHrCenter($cityId, 'Amy Center', 'مركز ي');
    insertHrCenter($cityId, 'Closed Center', 'مركز مغلق', isActive: false);

    expect(array_keys(HrCenter::options()))->toBe([$amy, $zed]);

    app()->setLocale('ar');

    expect(array_keys(HrCenter::options()))->toBe([$zed, $amy])
        ->and(array_values(HrCenter::options())[0])->toBe('مركز أ');
});

test('a remembered center is found by id, including an inactive one, and null when HR has none', function () {
    $centerId = insertHrCenter(insertHrCity(), 'Old Center', isActive: false);

    expect(HrCenter::remembered($centerId))
        ->name->toBe('Old Center')
        ->is_active->toBeFalse()
        ->and(HrCenter::remembered($centerId + 100))->toBeNull();
});

test('job level options are ordered by grade', function () {
    $senior = insertHrJobLevel('Senior', 'أول', 4);
    $junior = insertHrJobLevel('Junior', 'مبتدئ', 1);

    expect(HrJobLevel::options()->pluck('id')->all())->toBe([$junior, $senior]);
})->skip('JobLevel::options() no longer exists; the position form reads job levels with its own query.');

test('a position reaches its center, job level, city and direct manager position', function () {
    $cityId = insertHrCity();
    $centerId = insertHrCenter($cityId);
    $levelId = insertHrJobLevel();
    $departmentId = insertHrDepartment();
    $managerPositionId = insertHrPosition($departmentId, 'Manager');
    $positionId = insertHrPosition($departmentId, directManagerId: $managerPositionId, centerId: $centerId, cityId: $cityId, jobLevelId: $levelId);

    $position = HrPosition::query()->with(['center', 'jobLevel', 'city', 'directManager'])->findOrFail($positionId);

    expect($position->center->id)->toBe($centerId)
        ->and($position->jobLevel->id)->toBe($levelId)
        ->and($position->city->id)->toBe($cityId)
        ->and($position->directManager->id)->toBe($managerPositionId);
});
