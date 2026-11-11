<?php

use App\Models\HrEmployee;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\User;

test('guests cannot fetch an employee photo', function () {
    $employee = insertHrEmployee();

    $this->get(route('employees.avatar', ['employee' => $employee]))->assertRedirect(route('login'));
});

test('a signed-in user gets the employee photo from HR storage, privately cached', function () {
    Storage::fake('hr');
    $employee = insertHrEmployee();
    $mediaId = insertHrAvatar($employee, 'sami.png');
    Storage::disk('hr')->put('public/'.$mediaId.'/sami.png', 'photo-bytes');

    $response = $this->actingAs(User::factory()->create())
        ->get(route('employees.avatar', ['employee' => $employee]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Cache-Control'))->toContain('private')
        ->and($response->streamedContent())->toBe('photo-bytes');
});

test('photos on HR private disk are read from its private folder', function () {
    Storage::fake('hr');
    $employee = insertHrEmployee();
    $mediaId = insertHrAvatar($employee, 'sami.jpg', disk: 'local', mimeType: 'image/jpeg');
    Storage::disk('hr')->put('private/'.$mediaId.'/sami.jpg', 'private-bytes');

    $response = $this->actingAs(User::factory()->create())
        ->get(route('employees.avatar', ['employee' => $employee]))
        ->assertOk();

    expect($response->streamedContent())->toBe('private-bytes');
});

test('an employee without a photo, or whose file is missing, gets HR default picture', function () {
    Storage::fake('hr');
    $withoutPhoto = insertHrEmployee();
    $missingFile = insertHrEmployee();
    insertHrAvatar($missingFile);
    $user = User::factory()->create();

    foreach ([$withoutPhoto, $missingFile] as $employee) {
        $response = $this->actingAs($user)->get(route('employees.avatar', ['employee' => $employee]))->assertOk();

        expect($response->baseResponse->getFile()->getPathname())->toBe(public_path(HrEmployee::DefaultAvatar));
    }
});

test('the avatar url points at the photo route only when HR has a photo', function () {
    $withPhoto = insertHrEmployee();
    $withoutPhoto = insertHrEmployee();
    insertHrAvatar($withPhoto);

    expect(HrEmployee::avatarUrl($withPhoto))->toStartWith(route('employees.avatar', ['employee' => $withPhoto]).'?v=')
        ->and(HrEmployee::avatarUrl($withoutPhoto))->toBe(asset(HrEmployee::DefaultAvatar))
        ->and(HrEmployee::avatarUrl(null))->toBe(asset(HrEmployee::DefaultAvatar));
});

test('non-image files and file names with a path are never treated as a photo', function () {
    $pdf = insertHrEmployee();
    $traversal = insertHrEmployee();
    insertHrAvatar($pdf, 'cv.pdf', mimeType: 'application/pdf');
    insertHrAvatar($traversal, '../../../.env');

    expect(HrEmployee::avatarOf($pdf))->toBeNull()
        ->and(HrEmployee::avatarOf($traversal))->toBeNull();
});

test('a user avatar follows their linked HR employee', function () {
    $employee = insertHrEmployee();
    insertHrAvatar($employee);
    $user = User::factory()->create(['employee_id' => $employee]);

    expect($user->avatarUrl())->toBe(HrEmployee::avatarUrl($employee));
});
