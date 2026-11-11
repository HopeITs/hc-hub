{{--
    The folders and files of the open archive folder. Included by ⚡archives.blade.php.
--}}
@use('Modules\Hr\Support\Employees\HrMedia')

<x-ui.data-table :caption="$current?->name ?? $title">
    <x-slot:head>
        <th>{{ __('hr_employees.archives.folder_name') }}</th>
        <th class="w-40!">{{ __('hr_employees.archives.shared_with') }}</th>
        <th class="w-40!">{{ __('hr_employees.archives.updated') }}</th>
        <th class="w-40!"><span class="sr-only">{{ __('common.actions') }}</span></th>
    </x-slot:head>

    @foreach ($this->folders as $folder)
        @php
            $manage = $this->canManage($folder);
        @endphp
        <tr wire:key="folder-{{ $folder->id }}" data-folder="{{ $folder->id }}">
            <td>
                <a href="{{ route($routeName, ['folder' => $folder->id]) }}" wire:navigate class="flex items-center gap-2 font-semibold">
                    <flux:icon.folder class="size-5 text-primary" />
                    <span dir="auto">{{ $folder->name }}</span>
                </a>
            </td>
            <td class="tabular-nums">{{ (int) $folder->users_count }}</td>
            <td class="{{ $muted }}">{{ $folder->updated_at?->translatedFormat(__('formats.date')) }}</td>
            <td>
                <span class="flex items-center justify-end gap-1">
                    <x-ui.button tone="ghost" size="sm" icon="eye" :href="route($routeName, ['folder' => $folder->id])" wire:navigate :aria-label="__('common.view_name', ['name' => $folder->name])" />
                    @if ($manage)
                        @can('share', $folder)
                            <x-ui.button tone="ghost" size="sm" icon="user-plus" wire:click="openShare({{ $folder->id }})" :aria-label="__('hr_employees.archives.share')" />
                        @endcan
                        <x-ui.button tone="ghost" size="sm" icon="pencil-square" wire:click="openRename('folder', {{ $folder->id }})" :aria-label="__('hr_employees.archives.rename')" />
                        <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="confirmDelete('folder', {{ $folder->id }})" :aria-label="__('common.delete_name', ['name' => $folder->name])" />
                    @endif
                </span>
            </td>
        </tr>
    @endforeach

    @foreach ($this->files as $file)
        @php
            $extension = HrMedia::extension($file);
            $previewable = in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true);
        @endphp
        <tr wire:key="file-{{ $file->id }}" data-archive-file="{{ $file->id }}">
            <td>
                <span class="flex items-center gap-2">
                    <flux:icon.document class="size-5 {{ $muted }}" />
                    <span dir="auto">{{ $file->name }}</span>
                    <span class="text-xs uppercase {{ $muted }}">{{ $extension }}</span>
                </span>
            </td>
            <td class="{{ $muted }}">—</td>
            <td class="{{ $muted }}">{{ \Illuminate\Support\Carbon::parse($file->created_at)->translatedFormat(__('formats.date')) }}</td>
            <td>
                <span class="flex items-center justify-end gap-1">
                    @if ($previewable)
                        <x-ui.button tone="ghost" size="sm" icon="eye" :href="route('hr.archives.files.preview', $file->id)" target="_blank" :aria-label="__('common.view_name', ['name' => $file->name])" />
                    @endif
                    @can('archives.download')
                        <x-ui.button tone="ghost" size="sm" icon="arrow-down-tray" :href="route('hr.archives.files.download', $file->id)" :aria-label="__('hr_employees.archives.download')" />
                    @endcan
                    @if ($canManageHere)
                        <x-ui.button tone="ghost" size="sm" icon="pencil-square" wire:click="openRename('file', {{ $file->id }})" :aria-label="__('hr_employees.archives.rename')" />
                        <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="confirmDelete('file', {{ $file->id }})" :aria-label="__('common.delete_name', ['name' => $file->name])" />
                    @endif
                </span>
            </td>
        </tr>
    @endforeach
</x-ui.data-table>
