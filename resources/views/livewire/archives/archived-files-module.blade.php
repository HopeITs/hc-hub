<div>
    <header class="bg-white shadow">
        <div class="px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                {{ $isShared ? __('general.shared-folders') : __('general.my-folders') }}
            </h1>
        </div>
    </header>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="p-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between mb-6">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="{{ $isShared ? route('shared-folders.index') : route('my-folders.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                    <x-icons.home class="w-4 h-4 mr-2" />
                                    Root
                                </a>
                            </li>
                            @foreach ($breadcrumbs as $breadcrumb)
                                <li>
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 mx-1 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                                        </svg>
                                        <a href="{{ $isShared ? route('shared-folders.index', ['folderId' => $breadcrumb['id']]) : route('my-folders.index', ['folderId' => $breadcrumb['id']]) }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">
                                            {{ $breadcrumb['name'] }}
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </nav>

                    <div class="flex items-center space-x-2">
                        @if (!$currentFolder && (!$isShared || ($isShared && auth()->user()->hasRole('Super Admin'))))
                            <button type="button" wire:click="$set('showCreateFolderModal', true)" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 border border-transparent rounded-md shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <x-icons.plus class="w-5 h-5 mr-2 -ml-1" />
                                New Folder
                            </button>
                        @endif

                        @if ($currentFolder && (!$isShared || ($isShared && auth()->user()->hasRole('Super Admin'))))
                            <label for="file-upload" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm cursor-pointer hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <x-icons.upload class="w-5 h-5 mr-2 -ml-1" />
                                Upload Files
                                <input id="file-upload" wire:model.live="filesToUpload" type="file" class="sr-only" multiple accept=".jpg,.jpeg,.png,.gif,.xls,.xlsx,.doc,.docx,.pdf">
                            </label>
                        @endif
                    </div>
                </div>

                <div wire:loading wire:target="filesToUpload" class="mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: 100%"></div>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Uploading...</p>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    @forelse($folders as $folder)
                        <div class="relative group">
                            <a href="{{ $isShared ? route('shared-folders.index', ['folderId' => $folder->id]) : route('my-folders.index', ['folderId' => $folder->id]) }}" class="flex flex-col items-center justify-center p-4 text-center border rounded-lg hover:bg-gray-100 hover:shadow-md">
                                <x-icons.folder class="w-16 h-16 text-yellow-400" />
                                <span class="mt-2 text-sm font-medium text-gray-800 truncate">{{ $folder->name }}</span>
                                <span class="mt-1 text-xs text-gray-500">By: {{ optional($folder->creator->profile)->name ?? 'Unknown' }}</span>
                            </a>
                             @if (!$isShared || ($isShared && auth()->user()->hasRole('Super Admin')))
                                <div class="absolute top-2 right-2 flex flex-col space-y-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click.prevent="editPermissions({{ $folder->id }})" class="p-1 bg-white rounded-full text-gray-500 hover:text-blue-600 hover:bg-gray-100 focus:outline-none" title="Permissions">
                                        <x-icons.cog class="w-4 h-4" />
                                    </button>
                                    <button wire:click.prevent="startEditingFolder({{ $folder->id }})" class="p-1 bg-white rounded-full text-gray-500 hover:text-yellow-600 hover:bg-gray-100 focus:outline-none" title="Edit">
                                        <x-icons.pencil class="w-4 h-4" />
                                    </button>
                                    <button wire:click.prevent="confirmFolderDeletion({{ $folder->id }})" class="p-1 bg-white rounded-full text-gray-500 hover:text-red-600 hover:bg-gray-100 focus:outline-none" title="Delete">
                                        <x-icons.trash class="w-4 h-4" />
                                    </button>
                                </div>
                            @endif
                        </div>
                    @empty
                        @if ($files->isEmpty())
                            <p class="col-span-full text-center text-gray-500">This folder is empty.</p>
                        @endif
                    @endforelse

                    @foreach ($files as $file)
                        <div class="relative group">
                            <div wire:click="downloadFile({{ $file->id }})" class="flex flex-col items-center justify-center p-4 text-center border rounded-lg hover:bg-gray-100 hover:shadow-md cursor-pointer">
                                @if (isset($filePreviews[$file->id]))
                                    <img src="{{ $filePreviews[$file->id] }}" alt="{{ $file->name }}" class="w-16 h-16 object-cover rounded-md" />
                                @else
                                    <x-icons.document-text class="w-16 h-16 text-gray-500" />
                                @endif
                                <span class="mt-2 text-sm font-medium text-gray-800 break-all">{{ $file->name }}</span>
                            </div>
                            @if (!$isShared || ($isShared && auth()->user()->hasRole('Super Admin')))
                                <button wire:click.prevent="startEditingFile({{ $file->id }})" class="absolute top-2 right-8 p-1 bg-white rounded-full text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity hover:text-yellow-600 hover:bg-gray-100 focus:outline-none" title="Edit name">
                                    <x-icons.pencil class="w-4 h-4" />
                                </button>
                                <button wire:click.prevent="confirmFileDeletion({{ $file->id }})" class="absolute top-2 right-2 p-1 bg-white rounded-full text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity hover:text-red-600 hover:bg-gray-100 focus:outline-none" title="Delete file">
                                    <x-icons.trash class="w-4 h-4" />
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- All Modals (Create, Delete, Edit, Permissions) remain the same --}}
    @if ($showCreateFolderModal)
        <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="$set('showCreateFolderModal', false)"></div>
        <div class="fixed z-50 top-1/2 left-1/2 w-full max-w-lg -translate-x-1/2 -translate-y-1/2">
            <div class="mx-4 rounded-lg bg-white shadow-xl">
                <div class="px-6 pt-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Create New Folder</h3>
                    <div class="mt-4">
                        <label for="folderName" class="block text-sm font-medium text-gray-700">Folder Name</label>
                        <input type="text" id="folderName" wire:model.defer="newFolderName" wire:keydown.enter="createFolder" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        @error('newFolderName')
                        <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 mt-4 grid grid-cols-2 gap-3 rounded-b-lg">
                    <button type="button" wire:click="createFolder" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none">Create</button>
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
                        <input type="text" id="editingFolderName" wire:model.defer="editingFolderName" wire:keydown.enter="saveEditedFolder" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        @error('editingFolderName')
                        <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 mt-4 grid grid-cols-2 gap-3 rounded-b-lg">
                    <button wire:click="saveEditedFolder" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none">Save</button>
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
                        <input type="text" id="editingFileName" wire:model.defer="editingFileName" wire:keydown.enter="saveEditedFile" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        @error('editingFileName')
                        <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 mt-4 grid grid-cols-2 gap-3 rounded-b-lg">
                    <button wire:click="saveEditedFile" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none">Save</button>
                    <button wire:click="$set('editingFileId', null)" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-50">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showPermissionsModal)
        <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="closePermissionsModal"></div>
        <div class="fixed z-50 top-1/2 left-1/2 w-full max-w-lg -translate-x-1/2 -translate-y-1/2">
            <div class="mx-4 rounded-lg bg-white shadow-xl flex flex-col">
                <div class="px-6 pt-6">
                    <h3 class="text-lg font-medium text-gray-900">Assign Permissions for '{{ $folderForPermissions?->name }}'</h3>
                    <p class="mt-1 text-sm text-gray-500">Select the employees who can access this folder.</p>
                    <div class="mt-4">
                        <input type="text" wire:model.live.debounce.300ms="userSearch" placeholder="Search employees..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
                <div class="p-6 h-64 overflow-y-auto space-y-2">
                    @if($allUsers)
                        @forelse ($allUsers as $user)
                            <label for="user-{{ $user->id }}" class="flex items-center p-2 rounded-md hover:bg-gray-50 cursor-pointer">
                                <input id="user-{{ $user->id }}" type="checkbox" value="{{ $user->id }}" wire:model.live="selectedUsers" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">{{ optional($user->profile)->name ?? $user->email }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500 text-center">No employees found.</p>
                        @endforelse
                    @endif
                </div>
                <div class="bg-gray-50 px-6 py-4 grid grid-cols-2 gap-3 rounded-b-lg">
                    <button type="button" wire:click="savePermissions" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none">Save Permissions</button>
                    <button type="button" wire:click="closePermissionsModal" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-50">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
