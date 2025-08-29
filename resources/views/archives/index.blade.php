<x-layouts.app :title="__('general.archived-files')">
    <livewire:archives.archived-files-module :folderId="request()->route('folder')" />
</x-layouts.app>
