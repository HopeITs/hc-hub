<div>
    <div class="flex flex-col gap-4 md:flex-row md:justify-between md:items-start">
        <livewire:layout.breadcrumbs
            :title="$isShared ? __('general.shared-folders') : __('general.my-folders')"
            :crumbs="$finalCrumbs"
        />

        <div class="flex items-center flex-shrink-0 space-x-2">
            @if (!$currentFolder && !$isShared)
                <x-primary-button type="button" wire:click="$set('showCreateFolderModal', true)">
                    <x-icons.plus class="w-5 h-5 me-1" />
                        {{ __('general.new_folder') }}
                </x-primary-button>
            @endif

            @if ($currentFolder && (!$isShared || ($isShared && auth()->user()->hasRole('super-admin'))))
                <div
                    x-data="{ uploading: false, progress: 0 }"
                    x-on:livewire-upload-start="uploading = true"
                    x-on:livewire-upload-finish="uploading = false; progress = 0"
                    x-on:livewire-upload-error="uploading = false; progress = 0"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                    class="w-full"
                >
                    <label for="file-upload" class="flex items-center justify-center px-4 py-2 text-sm font-semibold text-white transition duration-150 ease-in-out rounded-md shadow-sm cursor-pointer bg-primary">
                        <x-icons.upload class="w-5 h-5 me-1" />
                        {{ __('general.upload_files') }}
                        <input id="file-upload" wire:model.live="filesToUpload" type="file" class="sr-only" multiple accept=".jpg,.jpeg,.png,.gif,.xls,.xlsx,.doc,.docx,.pdf">
                    </label>
                    <div x-show="uploading" class="mt-2 w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-primary h-2.5 rounded-full" :style="`width: ${progress}%`"></div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="p-6 mt-6 bg-white shadow-sm sm:rounded-lg">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            @forelse($folders as $folder)
            <div class="relative group ">
                <a href="{{ $isShared ? route('shared-folders.index', ['folderId' => $folder->id]) : route('my-folders.index', ['folderId' => $folder->id]) }}" class="flex flex-col items-center justify-center h-40 p-4 text-center border rounded-lg hover:bg-gray-100 hover:shadow-md">
                    <x-icons.folder class="w-16 h-16 text-primary" />
                    <span class="w-full mt-2 text-sm font-medium text-gray-800 truncate">{{ $folder->name }}</span>
                    @if ($folder->user_id === auth()->id())
                        <span class="mt-1 text-xs text-gray-500">{{ __('general.by_me') }}</span>
                    @else
                        <span class="mt-1 text-xs text-gray-500">{{ __('general.by') }}: {{ optional($folder->creator->profile)->name ?? __('general.unknown') }}</span>
                    @endif
                </a>

                @if (!$isShared && ($folder->user_id === auth()->id() || auth()->user()->hasRole('super-admin')))
                <div x-data="{ open: false }" @click.away="open = false" class="absolute top-2 right-2">
                    <button @click="open = !open" class="p-1 rounded-full hover:bg-secondary-soft ">
                        <svg class="w-5 h-5 text-secondary-medium"
                             viewBox="0 0 16 16"
                             fill="currentColor"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition
                         class="absolute right-0 z-20 w-48 mt-2 origin-top-right bg-white rounded-md shadow-lg"
                         style="display: none;"
                         @click="open = false">

                        <div class="py-1">
                            <div class="px-4 py-2 text-sm text-gray-500">
                                {{ __('general.files') }}: <span class="font-semibold text-gray-700">{{ $folder->files_count }}</span>
                            </div>
                            <div class="border-t border-gray-100"></div>
                            <button wire:click.prevent="editPermissions({{ $folder->id }})" class="flex items-center w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100">
                                <x-icons.cog class="text-red-800 me-3 size-5" />
                                {{ __('general.permissions') }}
                            </button>
                            <button wire:click.prevent="startEditingFolder({{ $folder->id }})" class="flex items-center w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100">
                                <svg class="text-gray-800 me-3 size-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                </svg>
                                {{ __('general.edit') }}
                            </button>
                            <button wire:click.prevent="confirmFolderDeletion({{ $folder->id }})" class="flex items-center w-full px-4 py-2 text-sm text-left text-red-700 hover:bg-gray-100">
                                <svg class="text-red-800 me-3 size-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                </svg>
                                {{ __('general.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @empty
                @if ($files->isEmpty())
                    <p class="text-center text-gray-500 col-span-full">{{ __('general.folder_empty') }}</p>
                @endif
            @endforelse

            @foreach ($files as $file)
            <div class="relative group">
                <div wire:click="downloadFile({{ $file->id }})" class="flex flex-col items-center justify-center h-40 p-4 text-center border rounded-lg cursor-pointer hover:bg-gray-100 hover:shadow-md">
                    @if (isset($filePreviews[$file->id]))
                    <img src="{{ $filePreviews[$file->id] }}" alt="{{ $file->name }}" class="object-cover w-16 h-16 rounded-md" />
                    @else
                    <x-icons.document-text class="w-16 h-16 text-primary" />
                    @endif
                    <span class="w-full mt-2 text-sm font-medium text-gray-800 truncate">{{ $file->name }}</span>
                </div>

                @if (!$isShared && ($currentFolder->user_id === auth()->id() || auth()->user()->hasRole('super-admin')))
                <div x-data="{ open: false }" @click.away="open = false" class="absolute top-2 right-2">
                    <button @click="open = !open" class="p-1 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 8a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                    </button>

                    <div x-show="open"
                         x-transition
                         class="absolute right-0 z-20 w-48 mt-2 origin-top-right bg-white rounded-md shadow-lg"
                         style="display: none;"
                         @click="open = false">

                        <div class="py-1">
                            <button wire:click.prevent="startEditingFile({{ $file->id }})" class="flex items-center w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100">
                                <svg class="text-gray-800 me-3 size-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                </svg>
                                {{ __('general.edit_name') }}
                            </button>
                            <button wire:click.prevent="confirmFileDeletion({{ $file->id }})" class="flex items-center w-full px-4 py-2 text-sm text-left text-red-700 hover:bg-gray-100">
                                <svg class="text-red-800 me-3 size-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                </svg>
                                {{ __('general.delete_file') }}
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    @if ($showCreateFolderModal)
    <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="$set('showCreateFolderModal', false)"></div>
    <div class="fixed z-50 w-full max-w-lg -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
        <div class="mx-4 bg-white rounded-lg shadow-xl">
            <div class="px-6 pt-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900">{{ __('general.create_new_folder') }}</h3>
                <div class="mt-4">
                    <label for="folderName" class="block text-sm font-medium text-gray-700">{{ __('general.folder_name') }}</label>
                    <input type="text" id="folderName" wire:model.defer="newFolderName" wire:keydown.enter="createFolder" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    @error('newFolderName')
                    <span class="block mt-2 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-6 py-4 mt-4 rounded-b-lg bg-gray-50">
                <x-primary-button type="button" wire:click="createFolder" :full="true">{{ __('general.create') }}</x-primary-button>
                <button type="button" wire:click="$set('showCreateFolderModal', false)" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">{{ __('general.cancel') }}</button>
            </div>
        </div>
    </div>
    @endif

    @if ($showDeleteConfirmationModal)
    <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="$set('showDeleteConfirmationModal', false)"></div>
    <div class="fixed z-50 w-full max-w-md -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
        <div class="mx-4 bg-white rounded-lg shadow-xl">
            <div class="px-6 pt-6">
                <div class="flex items-start">
                    <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 bg-red-100 rounded-full">
                        <x-icons.exclamation-triangle class="w-6 h-6 text-red-600" />
                    </div>
                    <div class="text-left ms-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('general.delete_item') }}</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">{{ __('general.delete_confirmation', ['item' => $itemNameToDelete ?? 'this item']) }}</strong>? This action cannot be undone.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-6 py-4 mt-4 rounded-b-lg bg-gray-50">
                <button type="button" wire:click="deleteItem" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none">{{ __('general.delete') }}</button>
                <button type="button" wire:click="$set('showDeleteConfirmationModal', false)" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">{{ __('general.cancel') }}</button>
            </div>
        </div>
    </div>
    @endif

    @if ($editingFolderId)
    <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="$set('editingFolderId', null)"></div>
    <div class="fixed z-50 w-full max-w-lg -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
        <div class="mx-4 bg-white rounded-lg shadow-xl">
            <div class="px-6 pt-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('general.edit_folder') }}</h3>
                <div class="mt-4">
                    <label for="editingFolderName" class="...">{{ __('general.folder_name') }}</label>
                    <input type="text" id="editingFolderName" wire:model.defer="editingFolderName" wire:keydown.enter="saveEditedFolder" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    @error('editingFolderName')
                    <span class="block mt-2 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-6 py-4 mt-4 rounded-b-lg bg-gray-50">
                <x-primary-button type="button" wire:click="saveEditedFolder" :full="true">{{ __('general.save') }}</x-primary-button>
                <button wire:click="$set('editingFolderId', null)" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-50">{{ __('general.cancel') }}</button>
            </div>
        </div>
    </div>
    @endif

    @if ($editingFileId)
    <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="$set('editingFileId', null)"></div>
    <div class="fixed z-50 w-full max-w-lg -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
        <div class="mx-4 bg-white rounded-lg shadow-xl">
            <div class="px-6 pt-6">
                <h3 class="...">{{ __('general.edit_file_name') }}</h3>
                <div class="mt-4">
                    <label for="editingFileName" class="...">{{ __('general.file_name') }}</label>
                    <input type="text" id="editingFileName" wire:model.defer="editingFileName" wire:keydown.enter="saveEditedFile" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    @error('editingFileName')
                    <span class="block mt-2 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-6 py-4 mt-4 rounded-b-lg bg-gray-50">
                <x-primary-button type="button" wire:click="saveEditedFile" :full="true">{{ __('general.save') }}</x-primary-button>
                <button wire:click="$set('editingFileId', null)" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-50">{{ __('general.cancel') }}</button>
            </div>
        </div>
    </div>
    @endif

    @if ($showPermissionsModal)
    <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="closePermissionsModal"></div>
    <div class="fixed z-50 w-full max-w-2xl -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
        <div class="flex flex-col mx-4 bg-white rounded-lg shadow-xl">
            <div class="px-6 pt-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('general.assign_permissions', ['folder' => $folderForPermissions?->name]) }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('general.select_employees') }}</p>
                <div class="mt-4">
                    <input type="text" wire:model.live.debounce.300ms="userSearch" placeholder="{{ __('general.search_employees') }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                </div>
                <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-3">
                    <div class="md:col-span-1">
                        <label for="pFilterType" class="block text-sm font-medium text-gray-700">{{ __('general.filter_by_category') }}</label>
                        <select id="pFilterType" wire:model.live="permissionFilterType" class="block w-full py-2 pe-10 mt-1 text-base border-gray-300 rounded-md ps-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                            <option value="">{{ __('general.all_categories') }}</option>
                            @foreach($this->permissionFilterCategories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end md:col-span-2 gap-x-2">
                        @if(!empty($permissionFilterOptions))
                            <div class="flex-grow">
                                <label for="pFilterValue" class="block text-sm font-medium text-gray-700">{{ __('general.select_value') }}</label>
                                <select id="pFilterValue" wire:model.live="permissionFilterValue" class="block w-full py-2 pe-10 mt-1 text-base border-gray-300 rounded-md ps-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                                    <option value="">{{ __('general.all') }}</option>
                                    @foreach($permissionFilterOptions as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-primary-button
                                type="button"
                                wire:click="addFilter"
                                :disabled="!$permissionFilterType || !$permissionFilterValue"
                                class="h-10">
                                {{ __('general.add_filter') }}
                            </x-primary-button>
                        @endif
                    </div>
                </div>

                @if (!empty($activeFilters))
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach ($activeFilters as $index => $filter)
                        <span class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-100 px-2 py-1 text-sm font-medium text-gray-800">
                            {{ $filter['type_label'] }}: {{ $filter['value_label'] }}
                            <button type="button" wire:click="removeFilter({{ $index }})" class="group relative -me-1 h-3.5 w-3.5 rounded-sm hover:bg-gray-500/20">
                                <span class="sr-only">Remove</span>
                                <svg viewBox="0 0 14 14" class="h-3.5 w-3.5 stroke-gray-600/50 group-hover:stroke-gray-600/75">
                                    <path d="M4 4l6 6m0-6l-6 6" />
                                </svg>
                            </button>
                        </span>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="relative h-64 p-6 space-y-2 overflow-y-auto" wire:loading.class="opacity-50" wire:target="permissionFilterType, permissionFilterValue, userSearch, addFilter, removeFilter">
                <div wire:loading wire:target="permissionFilterType, permissionFilterValue, userSearch, addFilter, removeFilter" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75">
                    <x-icons.spinner class="w-8 h-8 text-primary" />
                </div>

                @if($allUsers?->isNotEmpty())
                    <div class="flex items-center justify-end px-2 mb-2">
                        <button wire:click="toggleSelectAllVisible" type="button" class="text-sm font-medium text-primary hover:underline">
                            {{ $allVisibleUsersSelected ? __('general.deselect_all_visible') : __('general.select_all_visible') }}
                        </button>
                    </div>
                @endif

                @if($allUsers)
                @forelse ($allUsers as $user)
                <label for="user-{{ $user->id }}" class="flex items-center p-2 rounded-md hover:bg-gray-50 cursor-pointer @if($user->id === $folderForPermissions?->user_id) opacity-60 cursor-not-allowed @endif">
                    <input id="user-{{ $user->id }}" type="checkbox" value="{{ $user->id }}" wire:model.live="selectedUsers" class="w-4 h-4 border-gray-300 rounded text-primary focus:ring-primary" @if($user->id === $folderForPermissions?->user_id) disabled @endif>
                    <span class="text-sm text-gray-700 ms-3">
                        {{ optional($user->profile)->name ?? $user->email }}
                        @if($user->id === $folderForPermissions?->user_id) {{ __('general.me_creator') }} @endif
                    </span>
                </label>
                @empty
                <p class="py-4 text-sm text-center text-gray-500">{{ __('general.no_employees_found') }}</p>
                @endforelse
                @endif
            </div>
            <div class="grid grid-cols-2 gap-3 px-6 py-4 rounded-b-lg bg-gray-50">
                <x-primary-button type="button" wire:click="savePermissions" :full="true">{{ __('general.save_permissions') }}</x-primary-button>
                <button type="button" wire:click="closePermissionsModal" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">{{ __('general.cancel') }}</button>
            </div>
        </div>
    </div>
    @endif
</div>
