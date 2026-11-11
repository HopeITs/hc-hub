<?php

use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Employees\ManageArchive;
use Modules\Hr\Models\Folder;
use Modules\Hr\Support\Employees\HrMedia;

/*
 * Archive folders: "My folders" (private, mine) and "Shared folders" (shared
 * ones, plus others' private folders I was given). A folder is seen by the
 * people it is shared with; only its creator (or an administrator) changes it.
 */
new #[Layout('layouts::desk'), Title('hr_employees.titles.archives')] class extends Component {
    use WithFileUploads;

    #[Locked]
    public bool $isShared = false;

    #[Locked]
    public ?int $folderId = null;

    /** @var list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    public bool $showFolderModal = false;

    public string $newFolderName = '';

    public bool $showRenameModal = false;

    #[Locked]
    public string $renaming = '';

    #[Locked]
    public ?int $renamingId = null;

    public string $editingName = '';

    public bool $showShareModal = false;

    #[Locked]
    public ?int $sharingId = null;

    /** @var list<string> */
    public array $shareWith = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public string $deleting = '';

    #[Locked]
    public ?int $deletingId = null;

    #[Locked]
    public string $deletingName = '';

    public function mount(?Folder $folder = null): void
    {
        Gate::authorize('viewAny', Folder::class);

        $this->isShared = request()->routeIs('hr.archives.shared');

        if ($folder?->exists) {
            Gate::authorize('view', $folder);
            $this->folderId = $folder->id;
        }
    }

    #[Computed]
    public function current(): ?Folder
    {
        return $this->folderId === null ? null : Folder::query()->select(['id', 'name', 'parent_id', 'user_id', 'is_shared'])->findOrFail($this->folderId);
    }

    /**
     * @return Collection<int, Folder>
     */
    #[Computed]
    public function folders(): Collection
    {
        $user = Auth::user();

        return Folder::query()
            ->select(['id', 'name', 'parent_id', 'user_id', 'is_shared', 'updated_at'])
            ->where('parent_id', $this->folderId)
            ->when($this->isShared,
                fn ($query) => $query->where(fn ($q) => $q->where('is_shared', true)->orWhere('user_id', '!=', $user->id)),
                fn ($query) => $query->where('is_shared', false)->where('user_id', $user->id))
            ->when(! $user->isAdministrator(), fn ($query) => $query->whereHas('users', fn ($q) => $q->whereKey($user->id)))
            ->withCount('users')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    #[Computed]
    public function files(): Collection
    {
        return $this->folderId === null ? collect() : HrMedia::for(HrMedia::FOLDER, $this->folderId, ManageArchive::COLLECTION);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    #[Computed]
    public function trail(): array
    {
        $trail = [];
        $folder = $this->current;

        while ($folder !== null && count($trail) < 20) {
            array_unshift($trail, ['id' => $folder->id, 'name' => $folder->name]);
            $folder = $folder->parent_id === null ? null : Folder::query()->select(['id', 'name', 'parent_id'])->find($folder->parent_id);
        }

        return $trail;
    }

    public function canManage(?Folder $folder): bool
    {
        $user = Auth::user();

        if ($this->isShared && ! $user->isAdministrator()) {
            return false;
        }

        return $folder === null ? $user->can('create', Folder::class) : $user->can('manage', $folder);
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function userOptions(): array
    {
        return User::query()
            ->select(['id', 'email', 'employee_id'])
            ->orderBy('email')
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => trim($user->name.' · '.$user->email, ' ·')])
            ->all();
    }

    public function openFolderModal(): void
    {
        abort_unless($this->canManage($this->current), 403);

        $this->resetValidation();
        $this->newFolderName = '';
        $this->showFolderModal = true;
    }

    public function closeFolderModal(): void
    {
        $this->showFolderModal = false;
    }

    public function createFolder(): void
    {
        abort_unless($this->canManage($this->current), 403);

        $folder = app(ManageArchive::class)->createFolder(Auth::user(), $this->newFolderName, $this->current, $this->isShared);

        $this->showFolderModal = false;
        unset($this->folders);
        Flux::toast(variant: 'success', text: __('hr_employees.archives.saved'));
        $this->openShare($folder->id);
    }

    public function updatedUploads(): void
    {
        $folder = $this->current;
        abort_unless($folder !== null && $this->canManage($folder), 403);

        $this->validate([
            'uploads' => ['array', 'max:20'],
            'uploads.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,docx,xlsx,pptx'],
        ], [], ['uploads.*' => __('hr_employees.archives.files')]);

        app(ManageArchive::class)->upload(Auth::user(), $folder, $this->uploads);

        $this->uploads = [];
        unset($this->files);
        Flux::toast(variant: 'success', text: __('hr_employees.archives.uploaded'));
    }

    public function openRename(string $kind, int $id): void
    {
        abort_unless(in_array($kind, ['folder', 'file'], true), 404);

        $folder = $kind === 'folder' ? Folder::query()->findOrFail($id) : $this->current;
        abort_unless($folder !== null && $this->canManage($folder), 403);

        $this->resetValidation();
        $this->renaming = $kind;
        $this->renamingId = $id;
        $this->editingName = $kind === 'folder' ? $folder->name : (string) (HrMedia::find($id)?->name ?? '');
        $this->showRenameModal = true;
    }

    public function closeRename(): void
    {
        $this->showRenameModal = false;
        $this->renamingId = null;
    }

    public function saveRename(): void
    {
        $actions = app(ManageArchive::class);

        if ($this->renaming === 'folder') {
            $folder = Folder::query()->findOrFail((int) $this->renamingId);
            abort_unless($this->canManage($folder), 403);
            $actions->renameFolder(Auth::user(), $folder, $this->editingName);
            unset($this->folders);
        } else {
            abort_unless($this->current !== null && $this->canManage($this->current), 403);
            $actions->renameFile(Auth::user(), $this->current, (int) $this->renamingId, $this->editingName);
            unset($this->files);
        }

        $this->closeRename();
        Flux::toast(variant: 'success', text: __('hr_employees.archives.saved'));
    }

    public function openShare(int $folderId): void
    {
        $folder = Folder::query()->findOrFail($folderId);
        Gate::authorize('share', $folder);

        $this->sharingId = $folder->id;
        $this->shareWith = $folder->users()->pluck('users.id')->map(fn (mixed $id): string => (string) $id)->all();
        $this->showShareModal = true;
    }

    public function closeShare(): void
    {
        $this->showShareModal = false;
        $this->sharingId = null;
    }

    public function saveShare(): void
    {
        $folder = Folder::query()->findOrFail((int) $this->sharingId);

        app(ManageArchive::class)->share(Auth::user(), $folder, array_map('intval', $this->shareWith));

        $this->closeShare();
        unset($this->folders);
        Flux::toast(variant: 'success', text: __('hr_employees.archives.shared_done'));
    }

    public function confirmDelete(string $kind, int $id): void
    {
        abort_unless(in_array($kind, ['folder', 'file'], true), 404);

        $folder = $kind === 'folder' ? Folder::query()->findOrFail($id) : $this->current;
        abort_unless($folder !== null && $this->canManage($folder), 403);

        $this->deleting = $kind;
        $this->deletingId = $id;
        $this->deletingName = $kind === 'folder' ? $folder->name : (string) (HrMedia::find($id)?->name ?? '');
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function deleteConfirmed(): void
    {
        $actions = app(ManageArchive::class);

        if ($this->deleting === 'folder') {
            $folder = Folder::query()->findOrFail((int) $this->deletingId);
            abort_unless($this->canManage($folder), 403);
            $actions->deleteFolder(Auth::user(), $folder);
            unset($this->folders);
            Flux::toast(variant: 'success', text: __('hr_employees.archives.deleted'));
        } else {
            abort_unless($this->current !== null && $this->canManage($this->current), 403);
            $actions->deleteFile(Auth::user(), $this->current, (int) $this->deletingId);
            unset($this->files);
            Flux::toast(variant: 'success', text: __('hr_employees.archives.file_deleted'));
        }

        $this->cancelDelete();
    }
};
?>

@php
    $routeName = $isShared ? 'hr.archives.shared' : 'hr.archives.mine';
    $current = $this->current;
    $canManageHere = $this->canManage($current);
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $title = $isShared ? __('hr_employees.archives.shared') : __('hr_employees.archives.mine');
@endphp

<section class="@container/directory flex flex-col gap-5" data-archives="{{ $isShared ? 'shared' : 'mine' }}">
    <x-ui.detail-header
        :back="$current === null ? null : route($routeName, ['folder' => $current->parent_id])"
        :breadcrumbs="array_merge(
            [['label' => $title, 'url' => $current === null ? null : route($routeName)]],
            array_map(fn (array $crumb): array => ['label' => $crumb['name'], 'url' => $crumb['id'] === $folderId ? null : route($routeName, ['folder' => $crumb['id']])], $this->trail),
        )"
        :title="$current?->name ?? $title"
    >
        <x-slot:meta>
            <a href="{{ route('hr.archives.mine') }}" wire:navigate @class(['font-semibold underline' => ! $isShared])>{{ __('hr_employees.archives.mine') }}</a>
            <a href="{{ route('hr.archives.shared') }}" wire:navigate @class(['font-semibold underline' => $isShared])>{{ __('hr_employees.archives.shared') }}</a>
        </x-slot:meta>
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @if ($current !== null && $canManageHere)
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-panel-border px-3 py-2 text-sm font-semibold" data-test="archive-upload">
                        <flux:icon.arrow-up-tray class="size-4" />
                        {{ __('hr_employees.archives.upload') }}
                        <input type="file" multiple class="sr-only" wire:model="uploads" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx,.pptx">
                    </label>
                @endif
                @if ($canManageHere)
                    <x-ui.button tone="primary" icon="folder-plus" wire:click="openFolderModal" data-test="new-folder">{{ __('hr_employees.archives.new_folder') }}</x-ui.button>
                @endif
            </div>
        </x-slot:actions>
    </x-ui.detail-header>

    @error('uploads') <p class="text-sm text-status-critical">{{ $message }}</p> @enderror
    @error('uploads.*') <p class="text-sm text-status-critical">{{ $message }}</p> @enderror
    <p wire:loading wire:target="uploads" class="text-sm {{ $muted }}">{{ __('hr_employees.archives.uploading') }}</p>

    @if ($this->folders->isEmpty() && $this->files->isEmpty())
        <x-ui.empty-state icon="folder" :heading="$current === null ? __('hr_employees.archives.empty_heading') : __('hr_employees.archives.empty_files')" :body="$current === null ? __('hr_employees.archives.empty_body') : __('hr_employees.archives.allowed')" />
    @else
        @include('hr::pages.employees._archives-list')
    @endif

    @include('hr::pages.employees._archives-modals')
</section>
