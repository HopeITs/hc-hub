<div>
    <div class="flex flex-col gap-4 md:flex-row md:justify-between md:items-start">
        <livewire:layout.breadcrumbs
            :title="$isShared ? __('general.shared-folders') : __('general.my-folders')"
            :crumbs="$finalCrumbs"
        />

        <div class="flex items-center flex-shrink-0 gap-2">
            @if ($canManageCurrentFolder)
                <button type="button" wire:click="$set('showCreateFolderModal', true)" class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white transition rounded-md shadow-sm bg-[#be1f24] hover:bg-[#a51b20] focus:outline-none focus:ring-2 focus:ring-[#be1f24]/30">
                    <x-icons.plus class="w-5 h-5 me-1" />
                    {{ __('general.new_folder') }}
                </button>
            @endif

            @if ($currentFolder && $canManageCurrentFolder)
                <div
                    x-data="{ uploading: false, progress: 0 }"
                    x-on:livewire-upload-start="uploading = true"
                    x-on:livewire-upload-finish="uploading = false; progress = 0"
                    x-on:livewire-upload-error="uploading = false; progress = 0"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                    class="w-full sm:w-auto"
                >
                    <label for="file-upload" class="flex items-center justify-center px-4 py-2 text-sm font-semibold text-gray-800 transition duration-150 ease-in-out bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer hover:bg-gray-50">
                        <x-icons.upload class="w-5 h-5 me-1" />
                        {{ __('general.upload_files') }}
                        <input x-ref="fileInput" id="file-upload" wire:model.live="filesToUpload" type="file" class="sr-only" multiple accept=".jpg,.jpeg,.png,.svg,.pdf,.docx,.xlsx,.pptx">
                    </label>
                    <div x-show="uploading" class="mt-2 w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-[#be1f24] h-2.5 rounded-full" :style="`width: ${progress}%`"></div>
                    </div>
                    @error('filesToUpload')
                        <p class="mt-2 text-xs font-semibold text-[#be1f24]">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        </div>
    </div>

    <div
        @if ($currentFolder && $canManageCurrentFolder)
        x-data="{ dragging: false }"
        x-on:dragover.prevent="dragging = true"
        x-on:dragleave.prevent="dragging = false"
        x-on:drop.prevent="
            dragging = false;
            const input = document.getElementById('file-upload');
            if (input) {
                input.files = $event.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        "
        @endif
        class="relative p-5 mt-6 transition border border-gray-200 shadow-sm bg-gray-50/80 sm:rounded-lg"
        @if ($currentFolder && $canManageCurrentFolder) :class="dragging ? 'border-[#be1f24] bg-gray-100 ring-2 ring-[#be1f24]/20' : ''" @endif
    >
        @if ($currentFolder && $canManageCurrentFolder)
            <div x-show="dragging" x-transition class="absolute inset-3 z-10 flex items-center justify-center border-2 border-dashed rounded-lg pointer-events-none border-[#be1f24] bg-white/80" style="display: none;">
                <div class="text-center">
                    <x-icons.upload class="mx-auto size-10 text-[#be1f24]" />
                    <p class="mt-2 text-sm font-semibold text-gray-900">Drop files here to upload</p>
                    <p class="text-xs text-gray-500">JPG, PNG, SVG, PDF, DOCX, XLSX and PPTX files are supported</p>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            @forelse($folders as $folder)
            <div class="relative group ">
                <a href="{{ $isShared ? route('shared-folders.index', ['folderId' => $folder->id]) : route('my-folders.index', ['folderId' => $folder->id]) }}" class="flex flex-col items-center justify-center h-40 p-4 text-center transition bg-white border border-gray-200 rounded-lg shadow-sm hover:-translate-y-0.5 hover:border-[#be1f24]/50 hover:shadow-lg">
                    <span class="flex items-center justify-center rounded-lg size-16 bg-gray-100 ring-1 ring-gray-200 group-hover:bg-[#be1f24] group-hover:ring-[#be1f24]">
                        <x-icons.folder class="w-10 h-10 text-[#be1f24] group-hover:text-white" />
                    </span>
                    <span class="w-full mt-3 text-sm font-semibold text-gray-900 truncate">{{ $folder->name }}</span>
                    @if ($folder->user_id === auth()->id())
                        <span class="mt-1 text-xs text-gray-500">{{ __('general.by_me') }}</span>
                    @else
                        <span class="mt-1 text-xs text-gray-500">{{ __('general.by') }}: {{ optional($folder->creator->profile)->name ?? __('general.unknown') }}</span>
                    @endif
                </a>

                @if (!$isShared && ($folder->user_id === auth()->id() || auth()->user()->hasRole('super-admin')))
                <div x-data="{ open: false }" @click.away="open = false" class="absolute top-2 right-2">
                    <button @click="open = !open" class="inline-flex items-center justify-center transition rounded-md size-8 text-gray-500 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        <svg class="w-5 h-5 text-secondary-medium"
                             viewBox="0 0 16 16"
                             fill="currentColor"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition
                         class="absolute right-0 z-20 w-52 mt-2 overflow-hidden origin-top-right bg-white border border-gray-200 rounded-lg shadow-xl"
                         style="display: none;"
                         @click="open = false">

                        <div class="py-2">
                            <div class="px-4 pb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                {{ __('general.files') }} <span class="text-gray-800">{{ $folder->files_count }}</span>
                            </div>
                            <div class="border-t border-gray-100"></div>
                            <button wire:click.prevent="editPermissions({{ $folder->id }})" class="flex items-center w-full gap-3 px-4 py-2.5 text-sm font-medium text-left text-gray-700 transition hover:bg-gray-100 hover:text-gray-950">
                                <x-icons.cog class="flex-shrink-0 text-current size-5" />
                                <span>{{ __('general.permissions') }}</span>
                            </button>
                            <button wire:click.prevent="startEditingFolder({{ $folder->id }})" class="flex items-center w-full gap-3 px-4 py-2.5 text-sm font-medium text-left text-gray-700 transition hover:bg-gray-100 hover:text-gray-950">
                                <svg class="flex-shrink-0 text-current size-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                </svg>
                                <span>{{ __('general.edit') }}</span>
                            </button>
                            <button wire:click.prevent="confirmFolderDeletion({{ $folder->id }})" class="flex items-center w-full gap-3 px-4 py-2.5 text-sm font-medium text-left text-gray-700 transition hover:bg-gray-100 hover:text-gray-950">
                                <svg class="flex-shrink-0 text-current size-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                </svg>
                                <span>{{ __('general.delete') }}</span>
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
                <button type="button" wire:click="previewFile({{ $file->id }})" class="flex flex-col items-center justify-center w-full h-40 p-4 text-center transition bg-white border border-gray-200 rounded-lg shadow-sm cursor-pointer hover:-translate-y-0.5 hover:border-[#be1f24]/50 hover:shadow-lg">
                    @if (in_array(strtolower($file->extension ?? ''), ['jpg', 'jpeg', 'png'], true))
                    <img src="{{ route('folder-files.preview', ['media' => $file->id]) }}" alt="{{ $file->name }}" class="object-cover w-16 h-16 rounded-lg ring-1 ring-gray-200" />
                    @else
                    <span class="flex items-center justify-center rounded-lg size-16 bg-gray-100 ring-1 ring-gray-200 group-hover:bg-[#be1f24] group-hover:ring-[#be1f24]">
                        <x-icons.document-text class="w-10 h-10 text-[#be1f24] group-hover:text-white" />
                    </span>
                    @endif
                    <span class="w-full mt-3 text-sm font-semibold text-gray-900 truncate">{{ $file->name }}</span>
                    <span class="mt-1 text-xs text-gray-500">{{ strtoupper($file->extension) }} · {{ $file->size >= 1048576 ? number_format($file->size / 1048576, 1).' MB' : number_format($file->size / 1024, 1).' KB' }}</span>
                </button>

                <div x-data="{ open: false }" @click.away="open = false" class="absolute top-2 right-2">
                    <button @click="open = !open" class="inline-flex items-center justify-center transition rounded-md size-8 text-gray-500 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 8a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                    </button>

                    <div x-show="open"
                         x-transition
                         class="absolute right-0 z-20 w-52 mt-2 overflow-hidden origin-top-right bg-white border border-gray-200 rounded-lg shadow-xl"
                         style="display: none;"
                         @click="open = false">

                        <div class="py-2">
                            <button wire:click.prevent="previewFile({{ $file->id }})" class="flex items-center w-full gap-3 px-4 py-2.5 text-sm font-medium text-left text-gray-700 transition hover:bg-gray-100 hover:text-gray-950">
                                <x-icons.eye class="flex-shrink-0 text-current size-5" />
                                <span>Preview</span>
                            </button>
                            <button wire:click.prevent="downloadFile({{ $file->id }})" class="flex items-center w-full gap-3 px-4 py-2.5 text-sm font-medium text-left text-gray-700 transition hover:bg-gray-100 hover:text-gray-950">
                                <svg class="flex-shrink-0 text-current size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v11" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m7.5 9.5 4.5 4.5 4.5-4.5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 19h14" />
                                </svg>
                                <span>Download</span>
                            </button>
                            @if ($canManageCurrentFolder)
                            <div class="my-1 border-t border-gray-100"></div>
                            <button wire:click.prevent="startEditingFile({{ $file->id }})" class="flex items-center w-full gap-3 px-4 py-2.5 text-sm font-medium text-left text-gray-700 transition hover:bg-gray-100 hover:text-gray-950">
                                <svg class="flex-shrink-0 text-current size-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                </svg>
                                <span>{{ __('general.edit_name') }}</span>
                            </button>
                            <button wire:click.prevent="confirmFileDeletion({{ $file->id }})" class="flex items-center w-full gap-3 px-4 py-2.5 text-sm font-medium text-left text-gray-700 transition hover:bg-gray-100 hover:text-gray-950">
                                <svg class="flex-shrink-0 text-current size-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                </svg>
                                <span>{{ __('general.delete_file') }}</span>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
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
                    <input type="text" id="folderName" wire:model.defer="newFolderName" wire:keydown.enter="createFolder" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-[#be1f24] focus:border-[#be1f24] sm:text-sm">
                    @error('newFolderName')
                    <span class="block mt-2 text-sm text-[#be1f24]">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-6 py-4 mt-4 rounded-b-lg bg-gray-50">
                <button type="button" wire:click="createFolder" class="inline-flex justify-center w-full px-4 py-2 text-base font-semibold text-white transition rounded-md bg-[#be1f24] hover:bg-[#a51b20] focus:outline-none focus:ring-2 focus:ring-[#be1f24]/30">{{ __('general.create') }}</button>
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
                    <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 bg-gray-100 rounded-full">
                        <x-icons.exclamation-triangle class="w-6 h-6 text-[#be1f24]" />
                    </div>
                    <div class="text-left ms-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('general.delete_item') }}</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">{{ __('general.delete_confirmation', ['item' => $itemNameToDelete ?? 'this item']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-6 py-4 mt-4 rounded-b-lg bg-gray-50">
                <button type="button" wire:click="deleteItem" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-[#be1f24] rounded-md hover:bg-[#a51b20] focus:outline-none">{{ __('general.delete') }}</button>
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
                    <input type="text" id="editingFolderName" wire:model.defer="editingFolderName" wire:keydown.enter="saveEditedFolder" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-[#be1f24] focus:border-[#be1f24] sm:text-sm">
                    @error('editingFolderName')
                    <span class="block mt-2 text-sm text-[#be1f24]">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-6 py-4 mt-4 rounded-b-lg bg-gray-50">
                <button type="button" wire:click="saveEditedFolder" class="inline-flex justify-center w-full px-4 py-2 text-base font-semibold text-white transition rounded-md bg-[#be1f24] hover:bg-[#a51b20] focus:outline-none focus:ring-2 focus:ring-[#be1f24]/30">{{ __('general.save') }}</button>
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
                    <input type="text" id="editingFileName" wire:model.defer="editingFileName" wire:keydown.enter="saveEditedFile" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-[#be1f24] focus:border-[#be1f24] sm:text-sm">
                    @error('editingFileName')
                    <span class="block mt-2 text-sm text-[#be1f24]">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-6 py-4 mt-4 rounded-b-lg bg-gray-50">
                <button type="button" wire:click="saveEditedFile" class="inline-flex justify-center w-full px-4 py-2 text-base font-semibold text-white transition rounded-md bg-[#be1f24] hover:bg-[#a51b20] focus:outline-none focus:ring-2 focus:ring-[#be1f24]/30">{{ __('general.save') }}</button>
                <button wire:click="$set('editingFileId', null)" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-50">{{ __('general.cancel') }}</button>
            </div>
        </div>
    </div>
    @endif

    @if ($showPreviewModal && $previewFile)
    @php
        $previewExtension = strtolower($previewFile->extension ?? '');
        $canPreviewInOffice = in_array($previewExtension, ['docx', 'xlsx', 'pptx'], true);
        $canExtractOfficeText = in_array($previewExtension, ['docx', 'xlsx'], true);
    @endphp
    <div class="fixed inset-0 z-40 bg-gray-950/70" wire:click="closePreviewModal"></div>
    <div class="fixed z-50 w-full max-w-5xl -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
        <div class="flex flex-col max-h-[88vh] mx-4 overflow-hidden bg-white border border-gray-200 rounded-lg shadow-2xl">
            <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-gray-200 bg-gray-50">
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-gray-950 truncate">{{ $previewFile->name }}</h3>
                    <p class="mt-0.5 text-xs text-gray-500">{{ strtoupper($previewFile->extension) }} · {{ $previewFile->size >= 1048576 ? number_format($previewFile->size / 1048576, 1).' MB' : number_format($previewFile->size / 1024, 1).' KB' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="downloadFile({{ $previewFile->id }})" class="inline-flex items-center justify-center px-3 py-2 text-sm font-semibold text-white transition rounded-md bg-[#be1f24] hover:bg-[#a51b20]">
                        Download
                    </button>
                    <button type="button" wire:click="closePreviewModal" class="inline-flex items-center justify-center text-gray-500 transition rounded-md size-9 hover:bg-gray-100 hover:text-gray-900">
                        <span class="sr-only">Close</span>
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 min-h-0 p-4 overflow-auto bg-gray-100">
                @if (in_array($previewExtension, ['jpg', 'jpeg', 'png'], true))
                    <div class="flex items-center justify-center min-h-[55vh]">
                        <img src="{{ route('folder-files.preview', ['media' => $previewFile->id]) }}" alt="{{ $previewFile->name }}" class="max-w-full max-h-[70vh] rounded-lg shadow-lg bg-white">
                    </div>
                @elseif ($previewExtension === 'pdf')
                    <iframe src="{{ route('folder-files.preview', ['media' => $previewFile->id]) }}" title="{{ $previewFile->name }}" class="w-full h-[70vh] bg-white border border-gray-200 rounded-lg"></iframe>
                @elseif ($convertedOfficePdfUrl)
                    <iframe src="{{ $convertedOfficePdfUrl }}" title="{{ $previewFile->name }}" class="w-full h-[70vh] bg-white border border-gray-200 rounded-lg"></iframe>
                @elseif ($canExtractOfficeText)
                    @if (($officePreviewData['available'] ?? false) && ($officePreviewData['type'] ?? null) === 'docx')
                        <div class="max-w-4xl p-8 mx-auto bg-white border border-gray-200 rounded-lg shadow-sm">
                            <div class="prose prose-sm max-w-none">
                                @foreach ($officePreviewData['paragraphs'] as $paragraph)
                                    <p class="mb-3 leading-7 text-gray-800">{{ $paragraph }}</p>
                                @endforeach
                            </div>
                        </div>
                    @elseif (($officePreviewData['available'] ?? false) && ($officePreviewData['type'] ?? null) === 'xlsx')
                        <div class="overflow-auto bg-white border border-gray-200 rounded-lg shadow-sm">
                            <table class="min-w-full text-sm border-collapse">
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($officePreviewData['rows'] as $row)
                                        <tr class="divide-x divide-gray-100">
                                            @foreach ($row as $cell)
                                                <td class="min-w-32 px-3 py-2 text-gray-800 whitespace-nowrap">{{ $cell }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="flex items-center justify-center min-h-[55vh]">
                            <div class="max-w-lg p-8 text-center bg-white border border-gray-200 rounded-lg shadow-sm">
                                <span class="inline-flex items-center justify-center mx-auto rounded-lg size-16 bg-gray-100 ring-1 ring-gray-200">
                                    <x-icons.document-text class="size-9 text-[#be1f24]" />
                                </span>
                                <h4 class="mt-4 text-base font-bold text-gray-950">{{ $previewFile->name }}</h4>
                                <p class="mt-2 text-sm leading-6 text-gray-500">
                                    {{ $officePreviewData['message'] ?? 'A local preview could not be generated for this Office file.' }}
                                </p>
                                <button type="button" wire:click="downloadFile({{ $previewFile->id }})" class="inline-flex items-center justify-center px-4 py-2 mt-5 text-sm font-semibold text-white transition rounded-md bg-[#be1f24] hover:bg-[#a51b20]">
                                    Download file
                                </button>
                            </div>
                        </div>
                    @endif
                @elseif ($canPreviewInOffice)
                    <div class="flex items-center justify-center min-h-[55vh]">
                        <div class="max-w-lg p-8 text-center bg-white border border-gray-200 rounded-lg shadow-sm">
                            <span class="inline-flex items-center justify-center mx-auto rounded-lg size-16 bg-gray-100 ring-1 ring-gray-200">
                                <x-icons.document-text class="size-9 text-[#be1f24]" />
                            </span>
                            <h4 class="mt-4 text-base font-bold text-gray-950">{{ $previewFile->name }}</h4>
                            <p class="mt-2 text-sm leading-6 text-gray-500">
                                A full local Office preview needs LibreOffice or Microsoft Office available on the server. Until then, this file can be downloaded and opened in its native app.
                            </p>
                            <button type="button" wire:click="downloadFile({{ $previewFile->id }})" class="inline-flex items-center justify-center px-4 py-2 mt-5 text-sm font-semibold text-white transition rounded-md bg-[#be1f24] hover:bg-[#a51b20]">
                                Download file
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-center min-h-[55vh]">
                        <div class="max-w-md p-8 text-center bg-white border border-gray-200 rounded-lg shadow-sm">
                            <span class="inline-flex items-center justify-center mx-auto rounded-lg size-16 bg-gray-100 ring-1 ring-gray-200">
                                <x-icons.document-text class="size-9 text-[#be1f24]" />
                            </span>
                            <h4 class="mt-4 text-base font-bold text-gray-950">{{ $previewFile->name }}</h4>
                            <p class="mt-2 text-sm text-gray-500">Preview is available for images, PDF, Word, Excel and PowerPoint files. Download this file to open it in its native app.</p>
                            <button type="button" wire:click="downloadFile({{ $previewFile->id }})" class="inline-flex items-center justify-center px-4 py-2 mt-5 text-sm font-semibold text-white transition rounded-md bg-[#be1f24] hover:bg-[#a51b20]">
                                Download file
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if ($showPermissionsModal)
    <div class="fixed inset-0 z-40 bg-gray-950/65" wire:click="closePermissionsModal"></div>
    <div class="fixed z-50 w-full max-w-3xl -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
        <div class="flex flex-col mx-4 overflow-hidden bg-white border border-gray-200 rounded-lg shadow-2xl">
            <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-950">{{ __('general.assign_permissions', ['folder' => $folderForPermissions?->name]) }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ __('general.select_employees') }}</p>
                    </div>
                    <span class="inline-flex items-center justify-center flex-shrink-0 text-[#be1f24] bg-white border border-gray-200 rounded-lg shadow-sm size-10">
                        <x-icons.cog class="size-5" />
                    </span>
                </div>
                {{-- Search --}}
                <div class="mt-4">
                    <div class="relative">
                        <span class="absolute inset-y-0 flex items-center text-gray-400 pointer-events-none start-0 ps-3">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/>
                            </svg>
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="userSearch"
                               placeholder="{{ __('general.search_employees') }}"
                               class="block w-full border-gray-300 rounded-lg shadow-sm ps-10 focus:ring-[#be1f24] focus:border-[#be1f24] sm:text-sm">
                    </div>
                </div>

                {{-- Category filter builder --}}
                <div class="p-4 mt-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase">{{ __('general.filter_by_category') }}</span>
                        @if (!empty($activeFilters))
                            <button type="button" wire:click="clearFilters" class="text-xs font-semibold text-gray-500 transition hover:text-[#be1f24]">
                                {{ __('general.clear_all') }}
                            </button>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2 mt-2 sm:flex-row">
                        <select wire:model.live="permissionFilterType"
                                class="block w-full py-2 text-sm border-gray-300 rounded-md sm:w-1/2 focus:ring-[#be1f24] focus:border-[#be1f24]">
                            <option value="">{{ __('general.select_category') }}</option>
                            @foreach($this->permissionFilterCategories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        @if (!empty($permissionFilterOptions))
                            <select wire:model.live="permissionFilterValue"
                                    class="block w-full py-2 text-sm border-gray-300 rounded-md sm:w-1/2 focus:ring-[#be1f24] focus:border-[#be1f24]">
                                <option value="">{{ __('general.select_value') }}</option>
                                @foreach($permissionFilterOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    @if (!empty($activeFilters))
                        <div class="flex flex-wrap gap-2 mt-3">
                            @foreach ($activeFilters as $index => $filter)
                            <span class="inline-flex items-center rounded-full bg-gray-100 border border-gray-200 ps-3 pe-1.5 py-1 text-xs font-semibold text-gray-800">
                                {{ $filter['type_label'] }}: {{ $filter['value_label'] }}
                                <button type="button" wire:click="removeFilter({{ $index }})"
                                        class="inline-flex items-center justify-center w-4 h-4 text-[#be1f24] transition rounded-full ms-1.5 hover:bg-gray-200">
                                    <span class="sr-only">Remove</span>
                                    <svg viewBox="0 0 14 14" class="w-3 h-3 stroke-current"><path d="M4 4l6 6m0-6l-6 6" stroke-width="1.6"/></svg>
                                </button>
                            </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Result count + select all --}}
                @if ($allUsers)
                    <div class="flex items-center justify-between mt-4">
                        <p class="text-sm text-gray-500">
                            <span class="font-semibold text-gray-700">{{ $allUsers->count() }}</span> {{ trans_choice('general.employee', $allUsers->count()) }}
                            <span class="mx-1 text-gray-300">&middot;</span>
                            <span class="font-semibold text-[#be1f24]">{{ count($selectedUsers) }}</span> {{ __('general.selected') }}
                        </p>
                        @if ($allUsers->isNotEmpty())
                            <button wire:click="toggleSelectAllVisible" type="button" class="text-sm font-semibold transition text-[#be1f24] hover:underline">
                                {{ $allVisibleUsersSelected ? __('general.deselect_all_visible') : __('general.select_all_visible') }}
                            </button>
                        @endif
                    </div>
                @endif
            </div>
            <div class="relative mx-6 mt-4 overflow-y-auto border border-gray-200 rounded-lg shadow-inner h-72 bg-gray-50" wire:loading.class="opacity-40" wire:target="permissionFilterType, permissionFilterValue, userSearch, clearFilters, removeFilter, toggleSelectAllVisible">
                <div wire:loading wire:target="permissionFilterType, permissionFilterValue, userSearch, clearFilters, removeFilter, toggleSelectAllVisible" class="absolute inset-0 z-10 flex items-center justify-center bg-white/75">
                    <x-icons.spinner class="w-8 h-8 text-[#be1f24]" />
                </div>

                @if($allUsers)
                    <div class="p-2 space-y-2">
                        @forelse ($allUsers as $user)
                            @php
                                $isCreator = $user->id === $folderForPermissions?->user_id;
                                $isChecked = $isCreator || in_array((int) $user->id, array_map('intval', $selectedUsers), true);
                            @endphp
                            <button type="button"
                                    wire:key="perm-user-{{ $user->id }}"
                                    @if(!$isCreator) wire:click="toggleUser({{ $user->id }})" @endif
                                    class="flex items-center w-full gap-3 px-4 py-3 text-start transition rounded-lg border @if($isChecked) bg-white border-[#be1f24]/40 shadow-sm @elseif($isCreator) cursor-default bg-white border-gray-200 @else bg-white border-gray-200 hover:border-gray-400 hover:shadow-sm @endif">
                                <span class="flex items-center justify-center flex-shrink-0 transition-colors border rounded size-5 @if($isChecked) bg-[#be1f24] border-[#be1f24] text-white @else bg-white border-gray-300 @endif">
                                    @if($isChecked)
                                        <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                                    @endif
                                </span>
                                <span class="min-w-0 text-sm @if($isCreator) text-gray-500 @else text-gray-800 @endif">
                                    <span class="block font-semibold truncate">{{ optional($user->profile)->name ?? $user->email }}</span>
                                    @if($isCreator) <span class="text-xs text-gray-400">{{ __('general.me_creator') }}</span> @endif
                                </span>
                            </button>
                        @empty
                            <p class="py-10 text-sm text-center text-gray-500">{{ __('general.no_employees_found') }}</p>
                        @endforelse
                    </div>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-3 px-6 py-4 mt-5 border-t border-gray-200 bg-gray-50">
                <button type="button" wire:click="savePermissions" class="inline-flex justify-center w-full px-4 py-2 text-base font-semibold text-white transition rounded-md bg-[#be1f24] hover:bg-[#a51b20] focus:outline-none focus:ring-2 focus:ring-[#be1f24]/30">{{ __('general.save_permissions') }}</button>
                <button type="button" wire:click="closePermissionsModal" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">{{ __('general.cancel') }}</button>
            </div>
        </div>
    </div>
    @endif
</div>
