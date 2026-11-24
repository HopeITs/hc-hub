{{--
    The archive page's modals: new folder, rename, share, delete. Included by ⚡archives.blade.php.
--}}
<x-ui.modal wire:model="showFolderModal" size="sm" :title="__('hr_employees.archives.new_folder')" submit="createFolder" close="closeFolderModal">
    @if ($showFolderModal)
        <x-ui.form-input wire:model="newFolderName" :label="__('hr_employees.archives.folder_name')" maxlength="255" required />
        {{-- Whose folder this creates: the mode decides where it lives and who sees it. --}}
        <p class="mt-2 text-sm text-secondary-medium dark:text-ink-muted" data-test="new-folder-whose">{{ $isShared ? __('hr_employees.archives.new_folder_shared') : __('hr_employees.archives.new_folder_mine') }}</p>
    @endif
</x-ui.modal>

<x-ui.modal wire:model="showRenameModal" size="sm" :title="__('hr_employees.archives.rename')" submit="saveRename" close="closeRename">
    @if ($showRenameModal)
        <x-ui.form-input wire:model="editingName" :label="__('hr_employees.archives.folder_name')" maxlength="255" required />
    @endif
</x-ui.modal>

<x-ui.modal wire:model="showShareModal" size="md" :title="__('hr_employees.archives.share')" submit="saveShare" close="closeShare">
    @if ($showShareModal)
        {{-- Every login (HrPickerScopes::ArchiveUsers), fetched when the menu opens. --}}
        <x-ui.form-select wire:model="shareWith" wire:key="archive-share-{{ $sharingId }}" picker-scope="hr.archive-users" :picker-parameters="['folder' => $sharingId]" :label="__('hr_employees.archives.shared_with')" multiple />
    @endif
</x-ui.modal>

<x-ui.confirm-modal
    wire:model="showDeleteModal"
    close="cancelDelete"
    :title="$deleting === 'folder' ? __('hr_employees.archives.delete_folder_title') : __('hr_employees.archives.delete_file_title')"
    :message="$deleting === 'folder' ? __('hr_employees.archives.delete_folder_body') : __('hr_employees.archives.delete_file_body')"
    confirm="deleteConfirmed"
    :confirm-label="__('common.delete')"
    confirm-test="confirm-archive-delete"
/>
