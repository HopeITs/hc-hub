<?php

use App\Enums\FileType;
use App\Enums\MediaCollection;
use App\Models\Media;
use App\Support\StoredFiles;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\User;

/*
| hub:move-hr-media moves the HR app's files (hr_media rows, plain bytes on
| the hr disk) into the hub's encrypted media store. It reports first, copies
| only when told to, proves every copy by sha256, and never touches an
| original file or an hr_media row.
*/

beforeEach(function () {
    Storage::fake('hr');
    Storage::fake('local');
    $this->employee = insertHrEmployee();
    $this->uploader = (string) User::factory()->create()->id;
});

/**
 * An hr_media row of any owner type with its plain file on the hr disk.
 */
function insertHrFile(string $type, int $ownerId, string $collection, string $fileName, string $bytes, string $disk = 'local'): int
{
    $id = insertHrAvatar($ownerId, $fileName, $disk);
    DB::table('hr_media')->where('id', $id)->update(['model_type' => $type, 'collection_name' => $collection]);
    Storage::disk('hr')->put(($disk === 'local' ? 'private' : 'public')."/{$id}/{$fileName}", $bytes);

    return $id;
}

test('a dry run reports what each file would become and writes nothing', function () {
    $avatar = insertHrFile('App\Models\Employee', $this->employee, 'avatar', 'sami.png', 'photo', 'public');
    insertHrAvatar($this->employee, 'gone.png');

    $this->artisan('hub:move-hr-media')
        ->expectsOutputToContain('hr_media rows: 2')
        ->expectsOutputToContain('App\Models\Employee / avatar  ->  employee / avatar')
        ->expectsOutputToContain('file missing')
        ->expectsOutputToContain('Dry run: nothing was written.')
        ->assertSuccessful();

    expect(Media::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('hr')->allFiles())->toBe(["public/{$avatar}/sami.png"]);
});

test('a copy stores each file encrypted with a media row that points back, and leaves the original as it was', function () {
    $avatar = insertHrFile('App\Models\Employee', $this->employee, 'avatar', 'sami.png', 'photo-bytes', 'public');
    $paper = insertHrFile('App\Models\Employee', $this->employee, 'attachments', 'id-card.jpeg', 'card-bytes');
    $before = DB::table('hr_media')->orderBy('id')->get()->toArray();

    $this->artisan('hub:move-hr-media', ['--copy' => true, '--uploaded-by' => $this->uploader])->assertSuccessful();

    $copy = Media::query()->where('hr_media_id', $avatar)->sole();
    $card = Media::query()->where('hr_media_id', $paper)->sole();

    expect($copy->mediable_type)->toBe('employee')
        ->and($copy->mediable_id)->toBe($this->employee)
        ->and($copy->collection)->toBe(MediaCollection::Avatar)
        ->and($copy->type)->toBe(FileType::Png)
        ->and($copy->original_name)->toBe('sami.png')
        ->and($copy->size)->toBe(strlen('photo-bytes'))
        ->and($copy->uploaded_by)->toBe((int) $this->uploader)
        ->and($copy->isEncrypted())->toBeTrue()
        ->and(Storage::disk('local')->get($copy->path))->not->toContain('photo-bytes')
        ->and(StoredFiles::contents($copy->disk, $copy->path, $copy->encryption_key_id))->toBe('photo-bytes')
        ->and($card->collection)->toBe(MediaCollection::EmployeeAttachment)
        ->and($card->type)->toBe(FileType::Jpg)
        ->and($card->path)->toEndWith('.jpg')
        ->and(StoredFiles::contents($card->disk, $card->path, $card->encryption_key_id))->toBe('card-bytes')
        ->and(Storage::disk('hr')->get("public/{$avatar}/sami.png"))->toBe('photo-bytes')
        ->and(Storage::disk('hr')->get("private/{$paper}/id-card.jpeg"))->toBe('card-bytes')
        ->and(DB::table('hr_media')->orderBy('id')->get()->toArray())->toEqual($before);
});

test('a second copy run copies nothing twice', function () {
    insertHrFile('App\Models\Employee', $this->employee, 'avatar', 'sami.png', 'photo', 'public');
    $this->artisan('hub:move-hr-media', ['--copy' => true, '--uploaded-by' => $this->uploader])->assertSuccessful();
    $files = Storage::disk('local')->allFiles();

    $this->artisan('hub:move-hr-media', ['--copy' => true, '--uploaded-by' => $this->uploader])
        ->expectsOutputToContain('already copied')
        ->assertSuccessful();

    expect(Media::query()->count())->toBe(1)
        ->and(Storage::disk('local')->allFiles())->toBe($files);
});

test('verify passes when every copy decrypts to its original', function () {
    insertHrFile('App\Models\Employee', $this->employee, 'avatar', 'sami.png', 'photo', 'public');
    insertHrFile('App\Models\Employee', $this->employee, 'attachments', 'id.pdf', 'pdf');
    $this->artisan('hub:move-hr-media', ['--copy' => true, '--uploaded-by' => $this->uploader])->assertSuccessful();

    $this->artisan('hub:move-hr-media', ['--verify' => true])
        ->expectsOutputToContain('Verified 2/2 copies')
        ->assertSuccessful();
});

test('verify fails and names the file when a copy no longer matches its original', function (Closure $damage, string $problem) {
    $id = insertHrFile('App\Models\Employee', $this->employee, 'avatar', 'sami.png', 'photo', 'public');
    insertHrFile('App\Models\Employee', $this->employee, 'attachments', 'id.pdf', 'pdf');
    $this->artisan('hub:move-hr-media', ['--copy' => true, '--uploaded-by' => $this->uploader])->assertSuccessful();
    $damage(Media::query()->where('hr_media_id', $id)->sole(), $id);

    $this->artisan('hub:move-hr-media', ['--verify' => true])
        ->expectsOutputToContain('Verified 1/2 copies')
        ->expectsOutputToContain($problem)
        ->assertFailed();
})->with([
    'other bytes under the same key' => [fn (Media $copy) => StoredFiles::putEncrypted('local', $copy->path, 'not the photo'), 'sha256 differs'],
    'a corrupted copy' => [fn (Media $copy) => Storage::disk('local')->put($copy->path, 'garbage'), 'copy missing or unreadable'],
    'a deleted copy' => [fn (Media $copy) => Storage::disk('local')->delete($copy->path), 'copy missing or unreadable'],
    'an original that is gone' => [fn (Media $copy, int $id) => Storage::disk('hr')->delete("public/{$id}/sami.png"), 'original file missing'],
]);

test('a file the conversion tables do not know is reported and left where it is', function (string $type, string $collection, string $fileName, string $status) {
    $known = insertHrFile('App\Models\Employee', $this->employee, 'avatar', 'sami.png', 'photo', 'public');
    $unknown = insertHrFile($type, $this->employee, $collection, $fileName, 'bytes');

    $this->artisan('hub:move-hr-media', ['--copy' => true, '--uploaded-by' => $this->uploader])
        ->expectsOutputToContain($status)
        ->assertSuccessful();

    expect(Media::query()->pluck('hr_media_id')->all())->toBe([$known])
        ->and(Storage::disk('hr')->get("private/{$unknown}/{$fileName}"))->toBe('bytes');
})->with([
    'an owner type from the old app' => ['App\Models\Vehicle', 'file', 'car.pdf', 'unknown owner type'],
    'a collection the owner never had' => ['App\Models\Employee', 'payslips', 'may.pdf', 'unknown collection'],
    'a format media does not hold' => ['App\Models\Employee', 'attachments', 'scan.webp', 'file type not allowed in media'],
]);

test('a copy refuses to start without the account to record as the uploader', function (array $options) {
    insertHrFile('App\Models\Employee', $this->employee, 'avatar', 'sami.png', 'photo', 'public');

    $this->artisan('hub:move-hr-media', ['--copy' => true, ...$options])
        ->expectsOutputToContain('--uploaded-by')
        ->assertFailed();

    expect(Media::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'no account' => [[]],
    'an account that does not exist' => [['--uploaded-by' => '999999']],
]);

test('a copy refuses to start without a file encryption key', function () {
    insertHrFile('App\Models\Employee', $this->employee, 'avatar', 'sami.png', 'photo', 'public');
    useFileKey(null);

    $this->artisan('hub:move-hr-media', ['--copy' => true, '--uploaded-by' => $this->uploader])
        ->expectsOutputToContain('FILE_ENCRYPTION_KEY')
        ->assertFailed();

    expect(Media::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('the report can be written as a csv with one line per file', function () {
    $id = insertHrFile('App\Models\Employee', $this->employee, 'avatar', 'sami.png', 'photo', 'public');
    $path = storage_path('framework/testing/move-hr-media-'.uniqid().'.csv');

    $this->artisan('hub:move-hr-media', ['--csv' => $path])->assertSuccessful();

    $lines = array_map(fn (string $line): array => str_getcsv($line, ',', '"', ''), file($path, FILE_IGNORE_NEW_LINES));
    File::delete($path);

    expect($lines)->toHaveCount(2)
        ->and($lines[1])->toBe([(string) $id, 'App\Models\Employee', (string) $this->employee, 'avatar', 'public', "public/{$id}/sami.png", '1', '1', "employee#{$this->employee}", 'avatar', 'png', 'ready', '', '']);
});

test('the staff collections are never offered for project or proposal files', function () {
    $staffOnly = array_values(array_filter(MediaCollection::cases(), fn (MediaCollection $collection): bool => $collection->isStaffRecordOnly()));

    expect($staffOnly)->toBe([
        MediaCollection::Avatar,
        MediaCollection::EmployeeAttachment,
        MediaCollection::Credential,
        MediaCollection::ArchiveFile,
        MediaCollection::NewsImage,
    ]);
});

test('the hr_media_id migration rolls back and comes back', function () {
    $migration = 'database/migrations/2026_11_20_100410_add_hr_media_id_to_media_table.php';

    Artisan::call('migrate:rollback', ['--path' => $migration]);
    expect(Schema::hasColumn('media', 'hr_media_id'))->toBeFalse();

    Artisan::call('migrate', ['--path' => $migration]);
    expect(Schema::hasColumn('media', 'hr_media_id'))->toBeTrue();
});
