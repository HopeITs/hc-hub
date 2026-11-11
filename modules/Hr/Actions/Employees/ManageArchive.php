<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Employees;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Http\Controllers\Employees\ArchiveFileController;
use Modules\Hr\Models\Folder;
use Modules\Hr\Support\Employees\HrMedia;

/**
 * The writes of the archive folders (the old ArchivedFilesModule): folders
 * are created, renamed, shared and deleted by their creator (or an
 * administrator); the people a parent folder is shared with inherit a new
 * sub-folder; files are pdf / jpg / png / docx / xlsx / pptx up to 10 MB.
 */
class ManageArchive
{
    public const COLLECTION = 'archived_files';

    public function createFolder(User $actor, string $name, ?Folder $parent, bool $shared): Folder
    {
        Gate::forUser($actor)->authorize('create', Folder::class);

        if ($parent !== null) {
            Gate::forUser($actor)->authorize('manage', $parent);
        }

        $name = $this->validName($name, 'newFolderName');

        return DB::transaction(function () use ($actor, $name, $parent, $shared): Folder {
            $folder = new Folder;
            $folder->forceFill(['name' => $name, 'parent_id' => $parent?->id, 'user_id' => $actor->id, 'is_shared' => $shared])->save();

            $userIds = $parent === null ? collect() : $parent->users()->pluck('users.id');
            $folder->users()->sync($userIds->push($actor->id)->map(fn (mixed $id): int => (int) $id)->unique()->values()->all());

            return $folder;
        });
    }

    public function renameFolder(User $actor, Folder $folder, string $name): void
    {
        Gate::forUser($actor)->authorize('manage', $folder);

        $folder->forceFill(['name' => $this->validName($name, 'editingName')])->save();
    }

    /**
     * @param  list<int>  $userIds
     */
    public function share(User $actor, Folder $folder, array $userIds): void
    {
        Gate::forUser($actor)->authorize('share', $folder);

        $ids = collect($userIds)->map(fn (mixed $id): int => (int) $id)->push((int) $folder->user_id)->unique()->values();
        $ids = User::query()->whereIn('id', $ids)->pluck('id')->map(fn (mixed $id): int => (int) $id);
        $added = $ids->diff($folder->users()->pluck('users.id')->map(fn (mixed $id): int => (int) $id));

        $folder->users()->sync($ids->all());

        if ($added->isNotEmpty()) {
            // HR notified the people added (FolderShared); the hub's
            // notification for it is not wired yet.
            event('hr.folder.shared', [$folder->id, $added->values()->all(), $actor->id]);
        }
    }

    /**
     * Delete a folder, its sub-folders and every file in them.
     */
    public function deleteFolder(User $actor, Folder $folder): void
    {
        Gate::forUser($actor)->authorize('manage', $folder);

        $ids = [$folder->id];
        $frontier = [$folder->id];

        while ($frontier !== [] && count($ids) < 5000) {
            $frontier = Folder::query()->whereIn('parent_id', $frontier)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
            $ids = [...$ids, ...$frontier];
        }

        HrMedia::for(HrMedia::FOLDER, $ids, self::COLLECTION)->each(fn (object $media) => HrMedia::delete((int) $media->id));

        DB::transaction(function () use ($ids): void {
            DB::table('folder_user')->whereIn('folder_id', $ids)->delete();
            Folder::query()->whereIn('id', array_reverse($ids))->orderByDesc('id')->get()->each->delete();
        });
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function upload(User $actor, Folder $folder, array $files): int
    {
        Gate::forUser($actor)->authorize('manage', $folder);

        $key = 'archive-upload:'.$actor->id;

        if (RateLimiter::tooManyAttempts($key, 30)) {
            throw ValidationException::withMessages(['uploads' => __('hr_employees.archives.too_many')]);
        }

        RateLimiter::hit($key, 60);

        $stored = 0;

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());

            if (! in_array($extension, ArchiveFileController::ALLOWED_EXTENSIONS, true)) {
                throw ValidationException::withMessages(['uploads' => __('hr_employees.archives.allowed')]);
            }

            HrMedia::store($file, HrMedia::FOLDER, $folder->id, self::COLLECTION, ['validated_upload' => true]);
            $stored++;
        }

        return $stored;
    }

    public function renameFile(User $actor, Folder $folder, int $mediaId, string $name): void
    {
        Gate::forUser($actor)->authorize('manage', $folder);
        $this->ownedFile($folder, $mediaId);

        HrMedia::updateProperties($mediaId, [], $this->validName($name, 'editingName'));
    }

    public function deleteFile(User $actor, Folder $folder, int $mediaId): void
    {
        Gate::forUser($actor)->authorize('manage', $folder);
        $this->ownedFile($folder, $mediaId);

        HrMedia::delete($mediaId);
    }

    private function ownedFile(Folder $folder, int $mediaId): void
    {
        $media = HrMedia::find($mediaId);

        abort_if($media === null || $media->model_type !== HrMedia::FOLDER || (int) $media->model_id !== $folder->id, 404);
    }

    private function validName(string $name, string $key): string
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 255) {
            throw ValidationException::withMessages([$key => __('validation.required', ['attribute' => __('hr_employees.archives.folder_name')])]);
        }

        return $name;
    }
}
