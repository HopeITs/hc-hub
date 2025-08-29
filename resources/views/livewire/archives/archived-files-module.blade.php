<div>
    <header class="bg-white shadow">
        <div class="px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                {{ __('general.archived-files') }}
            </h1>
        </div>
    </header>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="p-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">

                {{-- This top section remains the same --}}
                <div class="flex items-center justify-between mb-6">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="{{ route('archived-files.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                    <x-icons.home class="w-4 h-4 mr-2" />
                                    Root
                                </a>
                            </li>
                            @foreach ($breadcrumbs as $breadcrumb)
                                <li>
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 mx-1 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                                        </svg>
                                        <a href="{{ route('archived-files.index', ['folder' => $breadcrumb['id']]) }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">
                                            {{ $breadcrumb['name'] }}
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </nav>

                    <div class="flex items-center space-x-2">
                        <button type="button" wire:click="$set('showCreateFolderModal', true)" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 border border-transparent rounded-md shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <x-icons.plus class="w-5 h-5 mr-2 -ml-1" />
                            New Folder
                        </button>

                        @if ($currentFolder)
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
                        <a href="{{ route('archived-files.index', ['folder' => $folder->id]) }}" class="flex flex-col items-center justify-center p-4 text-center border rounded-lg hover:bg-gray-100 hover:shadow-md">
                            <x-icons.folder class="w-16 h-16 text-yellow-400" />
                            <span class="mt-2 text-sm font-medium text-gray-800 truncate">{{ $folder->name }}</span>
                        </a>
                    @empty
                        @if ($files->isEmpty())
                            <p class="col-span-full text-center text-gray-500">This folder is empty.</p>
                        @endif
                    @endforelse

                    {{-- START: Updated File Display Logic --}}
                    @foreach ($files as $file)
                        <div class="flex flex-col text-center border rounded-lg hover:shadow-md">
                            {{-- This div is now a button that calls the downloadFile method --}}
                            <div wire:click="downloadFile({{ $file->id }})"
                               class="flex flex-col items-center justify-center flex-grow p-4 cursor-pointer">
                                @if (Str::startsWith($file->mime_type, 'image'))
                                    {{-- If it's an image, we still need getUrl for the thumbnail preview --}}
                                    <img src="{{ $file->getUrl('thumbnail') }}" alt="{{ $file->name }}" class="object-cover w-16 h-16">
                                @else
                                    {{-- Otherwise, show the generic document icon --}}
                                    <x-icons.document-text class="w-16 h-16 text-gray-500" />
                                @endif
                            </div>
                            <span class="p-2 text-sm font-medium text-gray-800 break-all border-t">{{ $file->name }}</span>
                        </div>
                    @endforeach
                    {{-- END: Updated File Display Logic --}}

                </div>
            </div>
        </div>
    </div>

    {{-- The modal code remains the same --}}
    @if ($showCreateFolderModal)
        <div class="fixed inset-0 z-40 bg-gray-900/60" wire:click="$set('showCreateFolderModal', false)"></div>
        <div class="fixed z-50 top-1/2 left-1/2 w-full max-w-lg -translate-x-1/2 -translate-y-1/2">
            <div class="mx-4 rounded-lg bg-white shadow-xl">
                <div class="px-6 pt-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Create New Folder</h3>
                    <div class="mt-4">
                        <label for="folderName" class="block text-sm font-medium text-gray-700">Folder Name</label>
                        <input type="text" id="folderName" wire:model.defer="newFolderName" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="e.g., Company Policies" wire:keydown.enter="createFolder">
                        @error('newFolderName')
                            <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 mt-4 grid grid-cols-2 gap-3 rounded-b-lg">
                    <button type="button" wire:click="createFolder" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm">
                        Create
                    </button>
                    <button type="button" wire:click="$set('showCreateFolderModal', false)" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
