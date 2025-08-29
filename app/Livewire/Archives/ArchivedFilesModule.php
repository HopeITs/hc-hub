<?php

namespace App\Livewire\Archives;

use App\Models\Folder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ArchivedFilesModule extends Component
{
    use WithFileUploads;

    public ?Folder $currentFolder = null;
    public Collection $folders;
    public Collection $files;
    public array $breadcrumbs = [];
    public bool $showCreateFolderModal = false;
    public string $newFolderName = '';
    public array $filesToUpload = [];
    public bool $showDeleteConfirmationModal = false;
    public ?int $folderToDeleteId = null;
    public ?int $fileToDeleteId = null;

    public function mount($folderId = null)
    {
        $this->currentFolder = $folderId ? Folder::find($folderId) : null;
        $this->loadContent();
    }

    public function loadContent(): void
    {
        $parentId = $this->currentFolder?->id;
        $this->folders = Folder::where('parent_id', $parentId)->orderBy('name')->get();
        $this->files = $this->currentFolder ? $this->currentFolder->getMedia() : collect();
        $this->generateBreadcrumbs();
    }

    protected function generateBreadcrumbs(): void
    {
        $this->breadcrumbs = [];
        $folder = $this->currentFolder;
        while ($folder) {
            array_unshift($this->breadcrumbs, ['id' => $folder->id, 'name' => $folder->name]);
            $folder = $folder->parent;
        }
    }

    public function createFolder()
    {
        $this->validate(['newFolderName' => 'required|string|max:255']);
        Folder::create([
            'name' => $this->newFolderName,
            'parent_id' => $this->currentFolder?->id,
        ]);
        $this->showCreateFolderModal = false;
        $this->newFolderName = '';
        $this->loadContent();
    }

    public function updatedFilesToUpload()
    {
        $this->validate([
            'filesToUpload.*' => 'mimes:jpg,jpeg,png,gif,xls,xlsx,doc,docx,pdf|max:10240',
        ]);

        if (!$this->currentFolder) {
            $this->addError('filesToUpload', 'Cannot upload files to the root. Please select a folder.');
            return;
        }

        foreach ($this->filesToUpload as $file) {
            $this->currentFolder->addMedia($file->getRealPath())->usingName($file->getClientOriginalName())->toMediaCollection('default', 'public');
        }

        $this->filesToUpload = [];
        $this->loadContent();
    }

    public function confirmFolderDeletion(int $folderId)
    {
        $this->folderToDeleteId = $folderId;
        $this->fileToDeleteId = null;
        $this->showDeleteConfirmationModal = true;
    }

    public function confirmFileDeletion(int $fileId)
    {
        $this->fileToDeleteId = $fileId;
        $this->folderToDeleteId = null;
        $this->showDeleteConfirmationModal = true;
    }

    public function deleteItem()
    {
        if ($this->folderToDeleteId) {
            $folder = Folder::find($this->folderToDeleteId);
            if ($folder) {
                $folder->delete();
            }
        }

        if ($this->fileToDeleteId) {
            $media = Media::find($this->fileToDeleteId);
            if ($media) {
                $media->delete();
            }
        }

        $this->showDeleteConfirmationModal = false;
        $this->reset('folderToDeleteId', 'fileToDeleteId');
        $this->loadContent();
    }

    public function render()
    {
        return view('livewire.archives.archived-files-module');
    }
}
