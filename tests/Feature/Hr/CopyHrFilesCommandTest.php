<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/*
| hub:copy-hr-files moves the hr_media files off the old HR app's folder and
| onto the hub's own hr disk. It copies, verifies, and never deletes or
| overwrites.
*/

beforeEach(function () {
    Storage::fake('hr');
    $this->source = storage_path('framework/testing/old-hr-'.uniqid());
    File::ensureDirectoryExists($this->source);
    $this->employee = insertHrEmployee();
});

afterEach(fn () => File::deleteDirectory($this->source));

function putOldHrFile(string $root, string $path, string $bytes): void
{
    File::ensureDirectoryExists(dirname($root.'/'.$path));
    File::put($root.'/'.$path, $bytes);
}

test('it copies each row\'s folder with its conversions and leaves the source alone', function () {
    $public = insertHrAvatar($this->employee, 'sami.png');
    $private = insertHrAvatar($this->employee, 'id.pdf', 'local', 'application/pdf');
    putOldHrFile($this->source, "public/{$public}/sami.png", 'photo');
    putOldHrFile($this->source, "public/{$public}/conversions/sami-thumb.png", 'thumb');
    putOldHrFile($this->source, "private/{$private}/id.pdf", 'pdf');

    $this->artisan('hub:copy-hr-files', ['--from' => $this->source])->assertSuccessful();

    expect(Storage::disk('hr')->get("public/{$public}/sami.png"))->toBe('photo')
        ->and(Storage::disk('hr')->get("public/{$public}/conversions/sami-thumb.png"))->toBe('thumb')
        ->and(Storage::disk('hr')->get("private/{$private}/id.pdf"))->toBe('pdf')
        ->and(File::get($this->source."/public/{$public}/sami.png"))->toBe('photo');
});

test('a dry run writes nothing', function () {
    $media = insertHrAvatar($this->employee, 'sami.png');
    putOldHrFile($this->source, "public/{$media}/sami.png", 'photo');

    $this->artisan('hub:copy-hr-files', ['--from' => $this->source, '--dry-run' => true])->assertSuccessful();

    expect(Storage::disk('hr')->allFiles())->toBe([]);
});

test('a file that differs on the hub disk is reported and kept, never overwritten', function () {
    $media = insertHrAvatar($this->employee, 'sami.png');
    putOldHrFile($this->source, "public/{$media}/sami.png", 'old-photo');
    Storage::disk('hr')->put("public/{$media}/sami.png", 'newer-photo');

    $this->artisan('hub:copy-hr-files', ['--from' => $this->source])
        ->expectsOutputToContain('differs on the hub disk')
        ->assertFailed();

    expect(Storage::disk('hr')->get("public/{$media}/sami.png"))->toBe('newer-photo');
});

test('a row whose file is missing at the source is reported and the rest are copied', function () {
    insertHrAvatar($this->employee, 'gone.png');
    $present = insertHrAvatar($this->employee, 'sami.png');
    putOldHrFile($this->source, "public/{$present}/sami.png", 'photo');

    $this->artisan('hub:copy-hr-files', ['--from' => $this->source])
        ->expectsOutputToContain('missing at source')
        ->assertSuccessful();

    expect(Storage::disk('hr')->get("public/{$present}/sami.png"))->toBe('photo');
});
