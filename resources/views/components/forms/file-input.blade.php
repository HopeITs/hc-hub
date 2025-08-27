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
                    <x-icons.user class="size-18 text-secondary-medium"/>

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
                <div class="w-24 rounded-md bg-gray-200 flex flex-col items-center justify-center">
                    <x-icons.image class="size-12"/>
                    <span class="text-xs text-secondary-medium">NO IMAGE</span>
                </div>
            @endif
        </div>
    @endif

    @if($previewType === 'pdf')
            <div class="shrink-0">
                @if ($oldFile instanceof Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                    <a href="{{ $oldFile->temporaryUrl() }}" target="_blank">
                        <x-icons.pdf-file />
                    </a>

                @elseif ($oldFile !== null)
                    <a href="{{$oldFile}}" class="w-24 rounded-md bg-gray-200 flex flex-col items-center justify-center">
                        <x-icons.pdf-file />
                        <span class="text-xs text-secondary-medium">{{$fileName ?? 'File'}}</span>
                    </a>

                @else
                    <div class="w-24 rounded-md bg-gray-200 flex flex-col items-center justify-center">
                        <x-icons.pdf-file />
                        <span class="text-xs text-secondary-medium">NO FILE</span>
                    </div>
                @endif
            </div>
    @endif
    <div>
        <input {{ $attributes->merge([
            'class' => 'block w-full text-sm text-slate-500 px-4 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100',
            'type' => 'file',
        ]) }}/>
        <x-error-span>{{ $errors->first($attributes->get('name')) }}</x-error-span>
    </div>
</div>
