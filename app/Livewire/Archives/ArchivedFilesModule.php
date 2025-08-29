<?php

namespace App\Livewire\Archives;

use App\Models\Folder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
    public $itemToDeleteType = null;
    public $itemToDeleteId = null;

    public array $filePreviews = [];

    public function mount($folderId = null)
    {
        $this->currentFolder = $folderId ? Folder::find($folderId) : null;
        $this->loadContent();
    }

    public function loadContent(): void
    {
        $parentId = $this->currentFolder?->id;
        $this->folders = Folder::where('parent_id', $parentId)->orderBy('name')->get();
        $this->files = $this->currentFolder ? $this->currentFolder->getMedia('archived_files') : collect();
        $this->generateBreadcrumbs();
        $this->generateFilePreviews();
    }

    public function generateFilePreviews(): void
    {
        $this->filePreviews = [];
        foreach ($this->files as $file) {
            if (Str::startsWith($file->mime_type, 'image')) {
                // Read the original image file, encode it, and store it for the view
                $this->filePreviews[$file->id] = 'data:' . $file->mime_type . ';base64,' . base64_encode(file_get_contents($file->getPath()));
            }
        }
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
            'parent_id' => null,
            'user_id' => auth()->id(),
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
        $this->itemToDeleteType = 'file';
        $this->itemToDeleteId = $mediaId;
        $this->showDeleteConfirmationModal = true;
    }

    public function confirmFolderDeletion($folderId)
    {
        $this->itemToDeleteType = 'folder';
        $this->itemToDeleteId = $folderId;
        $this->showDeleteConfirmationModal = true;
    }

    public function deleteItem()
    {
        if ($this->itemToDeleteType === 'file') {
            Media::findOrFail($this->itemToDeleteId)->delete();
        } elseif ($this->itemToDeleteType === 'folder') {
            Folder::findOrFail($this->itemToDeleteId)->delete();
        }
        $this->showDeleteConfirmationModal = false;
        $this->loadContent();
    }

    public function render()
    {
        return view('livewire.archives.archived-files-module');
    }
}
