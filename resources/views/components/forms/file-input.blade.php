@props([
    'previewType' => 'image', // image - docs - pdf - video
    'oldFile' => null,

])





<div class="mt-2 flex items-center space-x-6">
    @if ($previewType === 'avatar')
    <div class="shrink-0">
        @if ($oldFile instanceof Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
            <img class="size-20 rounded-full object-cover" src="{{ $oldFile->temporaryUrl() }}"
                 alt="New profile photo"/>
        @elseif ($oldFile !== null)
            <img class="size-20  rounded-full object-cover"
                 src="{{ $oldFile}}" alt="Current profile photo"/>
        @else
            <div class="size-20  rounded-full bg-gray-200 flex items-center justify-center">
                <svg class="h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                     viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
            </div>
        @endif
    </div>
    @endif
        @if ($previewType === 'image')
            <div class="shrink-0">
                @if ($oldFile instanceof Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                    <img class="w-28  rounded-md object-cover" src="{{ $oldFile->temporaryUrl() }}"
                         alt="New profile photo"/>
                @elseif ($oldFile !== null)
                    <img class="w-28  rounded-md object-cover"
                         src="{{ $oldFile}}" alt="Current profile photo"/>
                @else
                    <div class="w-24 rounded-md bg-gray-200 flex items-center justify-center">
                        <x-icons.user class="size-12"/>
{{--                        <svg class="h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"--}}
{{--                             viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">--}}
{{--                            <path stroke-linecap="round" stroke-linejoin="round"--}}
{{--                                  d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>--}}
{{--                        </svg>--}}
                    </div>
                @endif
            </div>
        @endif
    <div>
        <input     {{ $attributes->merge([
            'class' => 'block w-full text-sm text-slate-500 px-4 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100',
            'type' => 'file',
        ]) }}/>
        <x-error-span>{{ $errors->first($attributes->get('name')) }}</x-error-span>
    </div>
</div>
