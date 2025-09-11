<?php

namespace App\Livewire\Archives;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ArchivedFilesModule extends Component
{
    use WithFileUploads;

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
    public array $filePreviews = [];
    public bool $showPermissionsModal = false;
    public ?Folder $folderForPermissions = null;
    public ?Collection $allUsers = null;
    public array $selectedUsers = [];
    public string $userSearch = '';
    public ?int $editingFolderId = null;
    public string $editingFolderName = '';
    public ?int $editingFileId = null;
    public string $editingFileName = '';

    public function mount(bool $isShared, $folderId = null)
    {
        $this->isShared = $isShared;

        if ($folderId) {
            $folder = Folder::with('users')->find($folderId);
            if ($folder && !auth()->user()->hasRole('Super Admin') && !$folder->users->contains(auth()->id())) {
                abort(403, 'You do not have permission to access this folder.');
            }
            $this->currentFolder = $folder;
        }

        $this->loadContent();
    }

    private function authorizeSharedAction(): void
    {
        if ($this->isShared && !auth()->user()->hasRole('Super Admin')) {
            abort(403, 'You are not authorized to perform this action in Shared Folders.');
        }
    }

    public function loadContent(): void
    {
        $parentId = $this->currentFolder?->id;
        $query = Folder::where('parent_id', $parentId);

        if ($this->isShared) {
            $query->where(function ($q) {
                $q->where('is_shared', true)
                  ->orWhere(function($subQ) {
                      $subQ->where('is_shared', false)
                           ->where('user_id', '!=', auth()->id());
                  });
            });
        } else {
            $query->where('is_shared', false)->where('user_id', auth()->id());
        }

        if (!auth()->user()->hasRole('Super Admin')) {
            $query->whereHas('users', fn ($q) => $q->where('user_id', auth()->id()));
        }

        $this->folders = $query->orderBy('name')->get();
        $this->files = $this->currentFolder ? $this->currentFolder->getMedia('archived_files') : collect();
        $this->generateBreadcrumbs();
        $this->generateFilePreviews();
    }

    public function generateFilePreviews(): void
    {
        $this->filePreviews = [];
        foreach ($this->files as $file) {
            if (Str::startsWith($file->mime_type, 'image')) {
                $this->filePreviews[$file->id] = 'data:' . $file->mime_type . ';base64,' . base64_encode(file_get_contents($file->getPath()));
            }
        }
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
                'url' => $this->isShared ? route('shared-folders.index', ['folderId' => $folder->id]) : route('my-folders.index', ['folderId' => $folder->id])
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
            'user_id' => auth()->id(),
            'is_shared' => $this->isShared,
        ]);

        $folder->users()->attach(auth()->id());

        $this->showCreateFolderModal = false;
        $this->newFolderName = '';
        $this->editPermissions($folder->id);
    }

    public function editPermissions(int $folderId)
    {
        $folder = Folder::findOrFail($folderId);
        if ($folder->user_id !== auth()->id() && !auth()->user()->hasRole('Super Admin')) {
            abort(403, 'Only the creator can manage permissions.');
        }
        $this->folderForPermissions = $folder;
        $this->selectedUsers = $folder->users->pluck('id')->toArray();
        $this->showPermissionsModal = true;
    }

    public function savePermissions()
    {
        if ($this->folderForPermissions) {
            if ($this->folderForPermissions->user_id !== auth()->id() && !auth()->user()->hasRole('Super Admin')) {
                abort(403, 'Only the creator can manage permissions.');
            }
            $permissions = collect($this->selectedUsers);
            $permissions->push($this->folderForPermissions->user_id);
            $this->folderForPermissions->users()->sync($permissions->unique()->all());
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
    }

    public function updatedFilesToUpload()
    {
        $this->authorizeSharedAction();
        $this->validate(['filesToUpload.*' => 'mimes:jpg,jpeg,png,gif,xls,xlsx,doc,docx,pdf|max:10240']);

        if (!$this->currentFolder) {
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
        $this->itemToDeleteType = 'file';
        $this->itemToDeleteId = $mediaId;
        $this->showDeleteConfirmationModal = true;
    }

    public function confirmFolderDeletion($folderId)
    {
        $this->authorizeSharedAction();
        $this->itemToDeleteType = 'folder';
        $this->itemToDeleteId = $folderId;
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
        $this->editingFileName = $file->name;
    }

    public function saveEditedFile()
    {
        $this->authorizeSharedAction();
        $this->validate(['editingFileName' => 'required|string|max:255']);
        $file = Media::findOrFail($this->editingFileId);
        $file->name = $this->editingFileName;
        $file->save();
        $this->editingFileId = null;
        $this->editingFileName = '';
        $this->loadContent();
    }

    public function render()
    {
        if ($this->showPermissionsModal) {
            $users = User::with('profile')->whereHas('profile')->get();

            if (trim($this->userSearch)) {
                $users = $users->filter(function ($user) {
                    return Str::contains(optional($user->profile)->name, $this->userSearch, true);
                });
            }

            $creatorId = $this->folderForPermissions?->user_id;
            [$creator, $otherUsers] = $users->partition(fn($user) => $user->id === $creatorId);

            $this->allUsers = $creator->merge($otherUsers->sortBy('profile.name'));
        }

        return view('livewire.archives.archived-files-module');
    }
}
