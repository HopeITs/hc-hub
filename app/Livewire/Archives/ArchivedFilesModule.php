<?php

namespace App\Livewire\Archives;

use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use App\Models\Folder;
use App\Models\JobLevel;
use App\Models\Program;
use App\Models\User;
use App\Notifications\FolderShared;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ArchivedFilesModule extends Component
{
    use WithFileUploads;

    public User $user; // Store authenticated user once

    public bool $isShared;

    public ?Folder $currentFolder = null;

    public Collection $folders;

    public Collection $files;

    public array $finalCrumbs = [];

    public bool $showCreateFolderModal = false;

    public string $newFolderName = '';

    public array $filesToUpload = [];

    public bool $showDeleteConfirmationModal = false;

    public $itemToDeleteType = null;

    public $itemToDeleteId = null;

    public string $itemNameToDelete = '';

    public bool $showPermissionsModal = false;

    public ?Folder $folderForPermissions = null;

    public ?Collection $allUsers = null;

    public array $selectedUsers = [];

    public string $userSearch = '';

    public ?int $editingFolderId = null;

    public string $editingFolderName = '';

    public ?int $editingFileId = null;

    public string $editingFileName = '';

    public string $permissionFilterType = '';

    public string $permissionFilterValue = '';

    public array $permissionFilterOptions = [];

    public array $activeFilters = [];

    public bool $allVisibleUsersSelected = false;

    public function mount(bool $isShared, $folderId = null)
    {
        $this->user = auth()->user();
        $this->isShared = $isShared;

        if ($folderId) {
            $folder = Folder::with('users')->find($folderId);
            if ($folder && ! $this->user->hasRole('super-admin') && ! $folder->users->contains($this->user->id)) {
                abort(403, 'You do not have permission to access this folder.');
            }
            $this->currentFolder = $folder;
        }
        $this->loadContent();
    }

    public function getPermissionFilterCategoriesProperty(): array
    {
        return [
            'department' => 'Department',
            'job_level' => 'Job Level',
            'program' => 'Program',
            'center' => 'Center',
            'city' => 'City',
        ];
    }

    public function updatedPermissionFilterType($type)
    {
        $this->permissionFilterValue = '';
        $this->permissionFilterOptions = [];

        if ($type) {
            $collection = match ($type) {
                'department' => Department::all(),
                'program' => Program::all(),
                'job_level' => JobLevel::all(),
                'center' => Center::all(),
                'city' => City::all(),
                default => collect(),
            };

            $this->permissionFilterOptions = $collection
                ->mapWithKeys(fn ($item) => [$item->id => ($item->name ?? $item->name_en ?? $item->name_ar ?? (string) $item->id)])
                ->toArray();
        }
    }

    public function updatedPermissionFilterValue($value)
    {
        if ($this->permissionFilterType && $value) {
            $this->addFilter();
        }
    }

    public function addFilter()
    {
        if (! $this->permissionFilterType || ! $this->permissionFilterValue) {
            return;
        }

        $this->activeFilters[] = [
            'type' => $this->permissionFilterType,
            'value' => $this->permissionFilterValue,
            'type_label' => $this->permissionFilterCategories[$this->permissionFilterType],
            'value_label' => $this->permissionFilterOptions[$this->permissionFilterValue] ?? $this->permissionFilterValue,
        ];

        $this->permissionFilterType = '';
        $this->permissionFilterValue = '';
        $this->permissionFilterOptions = [];
        $this->allVisibleUsersSelected = false;
    }

    public function removeFilter($index)
    {
        if (isset($this->activeFilters[$index])) {
            unset($this->activeFilters[$index]);
            $this->activeFilters = array_values($this->activeFilters);
            $this->allVisibleUsersSelected = false;
        }
    }

    private function applyPermissionFilters(Builder $query): Builder
    {
        if (empty($this->activeFilters)) {
            return $query;
        }

        return $query->where(function ($q) {
            foreach ($this->activeFilters as $filter) {
                $q->whereHas('profile.contracts.position', function ($positionQuery) use ($filter) {
                    match ($filter['type']) {
                        'department' => $positionQuery->where('department_id', $filter['value']),
                        'job_level' => $positionQuery->where('job_level_id', $filter['value']),
                        'program' => $positionQuery->where('program_id', $filter['value']),
                        'center' => $positionQuery->where('center_id', $filter['value']),
                        'city' => $positionQuery->where('city_id', $filter['value']),
                        default => null,
                    };
                });
            }
        });
    }

    private function authorizeSharedAction(): void
    {
        if ($this->isShared && ! $this->user->hasRole('super-admin')) {
            abort(403, 'You are not authorized to perform this action in Shared Folders.');
        }
    }

    public function loadContent(): void
    {
        $parentId = $this->currentFolder?->id;
        $query = Folder::withCount('media as files_count');
        $query->where('parent_id', $parentId);

        if ($this->isShared) {
            $query->where(function ($q) {
                $q->where('is_shared', true)->orWhere(function ($subQ) {
                    $subQ->where('is_shared', false)->where('user_id', '!=', $this->user->id);
                });
            });
        } else {
            $query->where('is_shared', false)->where('user_id', $this->user->id);
        }

        if (! $this->user->hasRole('super-admin')) {
            $query->whereHas('users', fn ($q) => $q->where('user_id', $this->user->id));
        }

        $this->folders = $query->orderBy('name')->get();
        $this->files = $this->currentFolder ? $this->currentFolder->getMedia('archived_files') : collect();
        $this->generateBreadcrumbs();
    }

    protected function generateBreadcrumbs(): void
    {
        $baseCrumbUrl = $this->isShared ? route('shared-folders.index') : route('my-folders.index');
        $crumbs = [['label' => __('general.archived-files'), 'url' => $baseCrumbUrl]];
        $folderLinks = [];
        $folder = $this->currentFolder;

        while ($folder) {
            array_unshift($folderLinks, [
                'label' => $folder->name,
                'url' => $this->isShared ? route('shared-folders.index', ['folderId' => $folder->id]) : route('my-folders.index', ['folderId' => $folder->id]),
            ]);
            $folder = $folder->parent;
        }

        $this->finalCrumbs = array_merge($crumbs, $folderLinks);

        if (count($this->finalCrumbs) > 1) {
            $lastIndex = count($this->finalCrumbs) - 1;
            unset($this->finalCrumbs[$lastIndex]['url']);
        }
    }

    public function createFolder()
    {
        $this->authorizeSharedAction();
        $this->validate(['newFolderName' => 'required|string|max:255']);

        $folder = Folder::create([
            'name' => $this->newFolderName,
            'parent_id' => $this->currentFolder?->id,
            'user_id' => $this->user->id,
            'is_shared' => $this->isShared,
        ]);
        $folder->users()->attach($this->user->id);

        $this->showCreateFolderModal = false;
        $this->newFolderName = '';
        $this->loadContent();
        $this->editPermissions($folder->id);
    }

    public function editPermissions(int $folderId)
    {
        $folder = Folder::findOrFail($folderId);
        if ($folder->user_id !== $this->user->id && ! $this->user->hasRole('super-admin')) {
            abort(403, 'Only the creator can manage permissions.');
        }
        $this->folderForPermissions = $folder;
        $this->selectedUsers = $folder->users->pluck('id')->toArray();
        $this->showPermissionsModal = true;
    }

    public function savePermissions()
    {
        if ($this->folderForPermissions) {
            if ($this->folderForPermissions->user_id !== $this->user->id && ! $this->user->hasRole('super-admin')) {
                abort(403, 'Only the creator can manage permissions.');
            }

            $newIds = collect($this->selectedUsers)->map(fn ($id) => (int) $id);
            $newIds->push((int) $this->folderForPermissions->user_id);
            $newIds = $newIds->unique();

            $existingIds = $this->folderForPermissions->users->pluck('id');
            $addedUserIds = $newIds->diff($existingIds);

            $this->folderForPermissions->users()->sync($newIds);

            if ($addedUserIds->isNotEmpty()) {
                $creatorName = $this->user->profile?->name ?? '-';
                foreach (User::whereIn('id', $addedUserIds)->get() as $user) {
                    $user->notify(new FolderShared($this->folderForPermissions, $creatorName));
                }

                $this->dispatch('notification-created');
            }
        }

        $this->closePermissionsModal();
        $this->loadContent();
    }

    public function closePermissionsModal()
    {
        $this->showPermissionsModal = false;
        $this->folderForPermissions = null;
        $this->selectedUsers = [];
        $this->userSearch = '';
        $this->permissionFilterType = '';
        $this->permissionFilterValue = '';
        $this->permissionFilterOptions = [];
        $this->activeFilters = [];
        $this->allVisibleUsersSelected = false;
    }

    public function updatedFilesToUpload()
    {
        $this->authorizeSharedAction();
        $this->validate(['filesToUpload.*' => 'mimes:jpg,jpeg,png,gif,xls,xlsx,doc,docx,pdf|max:10240']);

        if (! $this->currentFolder) {
            $this->addError('filesToUpload', 'You must select a folder before uploading files.');

            return;
        }

        foreach ($this->filesToUpload as $file) {
            $this->currentFolder->addMedia($file->getRealPath())->usingName($file->getClientOriginalName())->toMediaCollection('archived_files', 'public');
        }
        $this->filesToUpload = [];
        $this->loadContent();
    }

    public function downloadFile($mediaId)
    {
        $mediaItem = Media::findOrFail($mediaId);

        return response()->download($mediaItem->getPath(), $mediaItem->name);
    }

    public function confirmFileDeletion($mediaId)
    {
        $this->authorizeSharedAction();
        $media = Media::findOrFail($mediaId);
        $this->itemToDeleteType = 'file';
        $this->itemToDeleteId = $mediaId;
        $this->itemNameToDelete = $media->name;
        $this->showDeleteConfirmationModal = true;
    }

    public function confirmFolderDeletion($folderId)
    {
        $this->authorizeSharedAction();
        $folder = Folder::findOrFail($folderId);
        $this->itemToDeleteType = 'folder';
        $this->itemToDeleteId = $folderId;
        $this->itemNameToDelete = $folder->name;
        $this->showDeleteConfirmationModal = true;
    }

    public function deleteItem()
    {
        $this->authorizeSharedAction();
        if ($this->itemToDeleteType === 'file') {
            Media::findOrFail($this->itemToDeleteId)->delete();
        } elseif ($this->itemToDeleteType === 'folder') {
            Folder::findOrFail($this->itemToDeleteId)->delete();
        }
        $this->showDeleteConfirmationModal = false;
        $this->itemNameToDelete = '';
        $this->loadContent();
    }

    public function startEditingFolder(int $folderId)
    {
        $this->authorizeSharedAction();
        $folder = Folder::findOrFail($folderId);
        $this->editingFolderId = $folder->id;
        $this->editingFolderName = $folder->name;
    }

    public function saveEditedFolder()
    {
        $this->authorizeSharedAction();
        $this->validate(['editingFolderName' => 'required|string|max:255']);
        $folder = Folder::findOrFail($this->editingFolderId);
        $folder->update(['name' => $this->editingFolderName]);
        $this->editingFolderId = null;
        $this->editingFolderName = '';
        $this->loadContent();
    }

    public function startEditingFile(int $fileId)
    {
        $this->authorizeSharedAction();
        $file = Media::findOrFail($fileId);
        $this->editingFileId = $file->id;
        $this->editingFileName = pathinfo($file->name, PATHINFO_FILENAME);
    }

    public function saveEditedFile()
    {
        $this->authorizeSharedAction();
        $this->validate(['editingFileName' => 'required|string|max:255']);
        $file = Media::findOrFail($this->editingFileId);

        $newFileName = $this->editingFileName.'.'.$file->extension;
        $file->name = $newFileName;
        $file->save();

        $this->editingFileId = null;
        $this->editingFileName = '';
        $this->loadContent();
    }

    /**
     * Toggle a single user in/out of the selection.
     *
     * Selection is managed entirely server-side (the checkboxes are plain
     * wire:click buttons, NOT wire:model). This avoids Livewire's array-checkbox
     * morphing bug that previously assigned `true` to $selectedUsers and crashed
     * the modal whenever the filtered list re-rendered.
     */
    public function toggleUser(int $userId): void
    {
        // The creator always keeps access and cannot be toggled off.
        if ($userId === (int) $this->folderForPermissions?->user_id) {
            return;
        }

        $selected = array_map('intval', $this->selectedUsers);

        if (in_array($userId, $selected, true)) {
            $selected = array_diff($selected, [$userId]);
        } else {
            $selected[] = $userId;
        }

        // array_values keeps a sequential list so Livewire serializes it as a JSON array.
        $this->selectedUsers = array_values(array_unique($selected));
    }

    public function toggleSelectAllVisible(): void
    {
        $visibleUserIds = $this->getVisibleUsersQuery()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $creatorId = (int) $this->folderForPermissions?->user_id;

        $toggleableUserIds = array_values(array_diff($visibleUserIds, [$creatorId]));
        $current = array_map('intval', $this->selectedUsers);

        // Decide from the actual selection state, not a stale flag.
        $allAlreadySelected = ! empty($toggleableUserIds) && empty(array_diff($toggleableUserIds, $current));

        if ($allAlreadySelected) {
            $this->selectedUsers = array_values(array_diff($current, $toggleableUserIds));
        } else {
            $this->selectedUsers = array_values(array_unique(array_merge($current, $toggleableUserIds)));
        }
    }

    public function clearFilters(): void
    {
        $this->activeFilters = [];
        $this->permissionFilterType = '';
        $this->permissionFilterValue = '';
        $this->permissionFilterOptions = [];
    }

    private function getVisibleUsersQuery(): Builder
    {
        $query = User::query()->whereHas('profile');

        if (trim($this->userSearch)) {
            $parts = array_filter(explode(' ', trim($this->userSearch)));

            $query->whereHas('profile', function ($q) use ($parts) {
                $q->where(function ($outer) use ($parts) {
                    foreach ($parts as $part) {
                        $outer->where(function ($inner) use ($part) {
                            $inner->where('first_name_en', 'LIKE', "%{$part}%")
                                ->orWhere('first_name_ar', 'LIKE', "%{$part}%")
                                ->orWhere('last_name_en', 'LIKE', "%{$part}%")
                                ->orWhere('last_name_ar', 'LIKE', "%{$part}%");
                        });
                    }
                });
            });
        }

        return $this->applyPermissionFilters($query);
    }

    public function render()
    {
        if ($this->showPermissionsModal) {
            $query = $this->getVisibleUsersQuery();
            $users = $query->get();

            $creatorId = $this->folderForPermissions?->user_id;
            [$creator, $otherUsers] = $users->partition(fn ($user) => $user->id === $creatorId);

            $selectedIds = array_map('intval', $this->selectedUsers);
            $sortedUsers = $otherUsers->sort(function ($firstUser, $secondUser) use ($selectedIds) {
                $firstHasAccess = in_array((int) $firstUser->id, $selectedIds, true);
                $secondHasAccess = in_array((int) $secondUser->id, $selectedIds, true);

                if ($firstHasAccess !== $secondHasAccess) {
                    return $firstHasAccess ? -1 : 1;
                }

                return strnatcasecmp(
                    optional($firstUser->profile)->name ?? $firstUser->email,
                    optional($secondUser->profile)->name ?? $secondUser->email
                );
            });

            $this->allUsers = $creator->merge($sortedUsers);

            // Keep the "select all visible" label in sync with the real selection,
            // so it flips to "deselect" once every toggleable visible user is selected.
            $toggleable = $otherUsers->pluck('id')->map(fn ($id) => (int) $id);
            $selected = array_map('intval', $this->selectedUsers);
            $this->allVisibleUsersSelected = $toggleable->isNotEmpty()
                && $toggleable->every(fn ($id) => in_array($id, $selected, true));
        }

        return view('livewire.archives.archived-files-module');
    }
}
