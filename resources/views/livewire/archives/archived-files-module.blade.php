<div>
    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
        <livewire:layout.breadcrumbs
            :title="$isShared ? __('general.shared-folders') : __('general.my-folders')"
            :crumbs="$finalCrumbs"
        />

        <div class="flex items-center space-x-2 flex-shrink-0">
            @if (!$currentFolder && !$isShared)
                <x-primary-button type="button" wire:click="$set('showCreateFolderModal', true)">
                    <x-icons.plus class="w-5 h-5 mr-2 -ml-1" />
                    New Folder
                </x-primary-button>
            @endif

            @if ($currentFolder && (!$isShared || ($isShared && auth()->user()->hasRole('Super Admin'))))
                <label for="file-upload" class="py-2 px-4 text-sm font-semibold text-white bg-primary hover:opacity-90 cursor-pointer rounded-md shadow-sm transition duration-150 ease-in-out flex justify-center items-center">
                    <x-icons.upload class="w-5 h-5 mr-2 -ml-1" />
                    Upload Files
                    <input id="file-upload" wire:model.live="filesToUpload" type="file" class="sr-only" multiple accept=".jpg,.jpeg,.png,.gif,.xls,.xlsx,.doc,.docx,.pdf">
                </label>
            @endif
        </div>
    </div>

    <div class="p-6 mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
        <div wire:loading.flex wire:target="filesToUpload" class="mb-4 items-center">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-primary h-2.5 rounded-full" style="width: 100%"></div>
            </div>
            <p class="text-sm text-gray-500 ml-4 flex-shrink-0">Uploading...</p>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            @forelse($folders as $folder)
            <div class="relative group">
                <a href="{{ $isShared ? route('shared-folders.index', ['folderId' => $folder->id]) : route('my-folders.index', ['folderId' => $folder->id]) }}" class="flex flex-col items-center justify-center p-4 text-center border rounded-lg h-40 hover:bg-gray-100 hover:shadow-md">
                    <x-icons.folder class="w-16 h-16 text-primary" />
                    <span class="w-full mt-2 text-sm font-medium text-gray-800 truncate">{{ $folder->name }}</span>
                    @if ($folder->user_id === auth()->id())
                        <span class="mt-1 text-xs text-gray-500 transition-opacity group-hover:opacity-0">By: Me</span>
                    @else
                        <span class="mt-1 text-xs text-gray-500 transition-opacity group-hover:opacity-0">By: {{ optional($folder->creator->profile)->name ?? 'Unknown' }}</span>
                    @endif
                </a>
                @if ($folder->user_id === auth()->id() || auth()->user()->hasRole('Super Admin'))
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center space-x-1 bg-white rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-all">
                    <button wire:click.prevent="editPermissions({{ $folder->id }})" class="cursor-pointer p-2 inline-flex items-center" title="Permissions">
                        <x-icons.cog class="size-5 text-red-800" />
                    </button>
                    <button wire:click.prevent="startEditingFolder({{ $folder->id }})" class="cursor-pointer p-2 inline-flex items-center" title="Edit">
                        <svg class="size-5 text-gray-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                        </svg>
                    </button>
                    <button wire:click.prevent="confirmFolderDeletion({{ $folder->id }})" class="cursor-pointer p-2" title="Delete">
                        <svg class="size-5 text-red-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                        </svg>
                    </button>
                </div>
                @endif
            </div>
            @empty
            <p class="col-span-full text-center text-gray-500">This folder is empty.</p>
            @endforelse

            @foreach ($files as $file)
            <div class="relative group">
                <div wire:click="downloadFile({{ $file->id }})" class="flex flex-col items-center justify-center p-4 text-center border rounded-lg h-40 hover:bg-gray-100 hover:shadow-md cursor-pointer">
                    @if (isset($filePreviews[$file->id]))
                    <img src="{{ $filePreviews[$file->id] }}" alt="{{ $file->name }}" class="object-cover w-16 h-16 rounded-md" />
                    @else
                    <x-icons.document-text class="w-16 h-16 text-primary" />
                    @endif
                    <span class="w-full mt-2 text-sm font-medium text-gray-800 truncate">{{ $file->name }}</span>
                </div>
                @if (!$isShared || ($isShared && auth()->user()->hasRole('Super Admin')))
                    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center space-x-1 bg-white rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-all">
                        <button wire:click.prevent="startEditingFile({{ $file->id }})" class="cursor-pointer p-2 inline-flex items-center" title="Edit name">
                            <svg class="size-5 text-gray-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                            </svg>
                        </button>
                        <button wire:click.prevent="confirmFileDeletion({{ $file->id }})" class="cursor-pointer p-2" title="Delete file">
                            <svg class="size-5 text-red-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                            </svg>
                        </button>
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    @if ($showCreateFolderModal)
    <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="$set('showCreateFolderModal', false)"></div>
    <div class="fixed z-50 top-1/2 left-1/2 w-full max-w-lg -translate-x-1/2 -translate-y-1/2">
        <div class="mx-4 rounded-lg bg-white shadow-xl">
            <div class="px-6 pt-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Create New Folder</h3>
                <div class="mt-4">
                    <label for="folderName" class="block text-sm font-medium text-gray-700">Folder Name</label>
                    <input type="text" id="folderName" wire:model.defer="newFolderName" wire:keydown.enter="createFolder" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    @error('newFolderName')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 mt-4 grid grid-cols-2 gap-3 rounded-b-lg">
                <x-primary-button type="button" wire:click="createFolder" :full="true">Create</x-primary-button>
                <button type="button" wire:click="$set('showCreateFolderModal', false)" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
            </div>
        </div>
    </div>
    @endif

    @if ($showDeleteConfirmationModal)
    <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="$set('showDeleteConfirmationModal', false)"></div>
    <div class="fixed z-50 top-1/2 left-1/2 w-full max-w-md -translate-x-1/2 -translate-y-1/2">
        <div class="mx-4 rounded-lg bg-white shadow-xl">
            <div class="px-6 pt-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <x-icons.exclamation-triangle class="h-6 w-6 text-red-600" />
                    </div>
                    <div class="ml-4 text-left">
                        <h3 class="text-lg font-medium text-gray-900">Delete Item</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Are you sure you want to delete this item? This action cannot be undone.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 mt-4 grid grid-cols-2 gap-3 rounded-b-lg">
                <button type="button" wire:click="deleteItem" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none">Delete</button>
                <button type="button" wire:click="$set('showDeleteConfirmationModal', false)" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
            </div>
        </div>
    </div>
    @endif

    @if ($editingFolderId)
    <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="$set('editingFolderId', null)"></div>
    <div class="fixed z-50 top-1/2 left-1/2 w-full max-w-lg -translate-x-1/2 -translate-y-1/2">
        <div class="mx-4 rounded-lg bg-white shadow-xl">
            <div class="px-6 pt-6">
                <h3 class="text-lg font-medium text-gray-900">Edit Folder</h3>
                <div class="mt-4">
                    <label for="editingFolderName" class="block text-sm font-medium text-gray-700">Folder Name</label>
                    <input type="text" id="editingFolderName" wire:model.defer="editingFolderName" wire:keydown.enter="saveEditedFolder" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    @error('editingFolderName')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 mt-4 grid grid-cols-2 gap-3 rounded-b-lg">
                <x-primary-button type="button" wire:click="saveEditedFolder" :full="true">Save</x-primary-button>
                <button wire:click="$set('editingFolderId', null)" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-50">Cancel</button>
            </div>
        </div>
    </div>
    @endif

    @if ($editingFileId)
    <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="$set('editingFileId', null)"></div>
    <div class="fixed z-50 top-1/2 left-1/2 w-full max-w-lg -translate-x-1/2 -translate-y-1/2">
        <div class="mx-4 rounded-lg bg-white shadow-xl">
            <div class="px-6 pt-6">
                <h3 class="text-lg font-medium text-gray-900">Edit File Name</h3>
                <div class="mt-4">
                    <label for="editingFileName" class="block text-sm font-medium text-gray-700">File Name</label>
                    <input type="text" id="editingFileName" wire:model.defer="editingFileName" wire:keydown.enter="saveEditedFile" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    @error('editingFileName')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 mt-4 grid grid-cols-2 gap-3 rounded-b-lg">
                <x-primary-button type="button" wire:click="saveEditedFile" :full="true">Save</x-primary-button>
                <button wire:click="$set('editingFileId', null)" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-50">Cancel</button>
            </div>
        </div>
    </div>
    @endif

    @if ($showPermissionsModal)
    <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="closePermissionsModal"></div>
    <div class="fixed z-50 top-1/2 left-1/2 w-full max-w-2xl -translate-x-1/2 -translate-y-1/2">
        <div class="mx-4 rounded-lg bg-white shadow-xl flex flex-col">
            <div class="px-6 pt-6">
                <h3 class="text-lg font-medium text-gray-900">Assign Permissions for '{{ $folderForPermissions?->name }}'</h3>
                <p class="mt-1 text-sm text-gray-500">Select the employees who can access this folder.</p>
                <div class="mt-4">
                    <input type="text" wire:model.live.debounce.300ms="userSearch" placeholder="Search employees by name..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                </div>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="pFilterType" class="block text-sm font-medium text-gray-700">Filter by:</label>
                        <select id="pFilterType" wire:model.live="permissionFilterType" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md accent-primary">
                            <option value="">All Categories</option>
                            @foreach($this->permissionFilterCategories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        @if(!empty($permissionFilterOptions))
                            <label for="pFilterValue" class="block text-sm font-medium text-gray-700">Select Value</label>
                            <select id="pFilterValue" wire:model.live="permissionFilterValue" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md accent-primary">
                                <option value="">All</option>
                                @foreach($permissionFilterOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
            </div>
            <div class="p-6 h-64 overflow-y-auto space-y-2 relative" wire:loading.class="opacity-50" wire:target="permissionFilterType, permissionFilterValue, userSearch">
                <div wire:loading wire:target="permissionFilterType, permissionFilterValue, userSearch" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center">
                    <x-icons.spinner class="h-8 w-8 text-primary" />
                </div>
                @if($allUsers)
                @forelse ($allUsers as $user)
                <label for="user-{{ $user->id }}" class="flex items-center p-2 rounded-md hover:bg-gray-50 cursor-pointer @if($user->id === $folderForPermissions?->user_id) opacity-60 cursor-not-allowed @endif">
                    <input id="user-{{ $user->id }}" type="checkbox" value="{{ $user->id }}" wire:model.live="selectedUsers" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary" @if($user->id === $folderForPermissions?->user_id) disabled @endif>
                    <span class="ml-3 text-sm text-gray-700">
                        {{ optional($user->profile)->name ?? $user->email }}
                        @if($user->id === $folderForPermissions?->user_id) (Me - Creator) @endif
                    </span>
                </label>
                @empty
                <p class="text-sm text-gray-500 text-center py-4">No employees found matching your criteria.</p>
                @endforelse
                @endif
            </div>
            <div class="bg-gray-50 px-6 py-4 grid grid-cols-2 gap-3 rounded-b-lg">
                <x-primary-button type="button" wire:click="savePermissions" :full="true">Save Permissions</x-primary-button>
                <button type="button" wire:click="closePermissionsModal" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
            </div>
        </div>
    </div>
    @endif
</div>
