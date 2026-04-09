@props([
    'previewType' => 'image',
    'oldFile' => null,
    'fileName' => null
])

<div class="mt-2 flex items-center space-x-6">
    @php
        $tempUrl = null;
        if ($oldFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            try {
                $tempUrl = $oldFile->temporaryUrl();
            } catch (\Exception $e) {
                $tempUrl = null;
            }
        }
    @endphp

    @if ($previewType === 'avatar')
        <div class="shrink-0">
            @if ($tempUrl)
                <img class="size-20 rounded-full object-cover" src="{{ $tempUrl }}" alt="New profile photo"/>
            @elseif ($oldFile && ! $tempUrl)
                @if (is_string($oldFile))
                    <img class="size-20 rounded-full object-cover" src="{{ $oldFile }}" alt="Current profile photo"/>
                @else
                    <div class="size-24 rounded-full flex border border-1 border-primary items-center justify-center">
                        <x-icons.user class="size-14 text-primary"/>
                    </div>
                @endif
            @else
                <div class="size-24 rounded-full flex border border-1 border-primary items-center justify-center">
                    <x-icons.user class="size-14 text-primary"/>
                </div>
            @endif
        </div>
    @endif

    @if ($previewType === 'image')
        <div class="shrink-0">
            @if ($tempUrl)
                <img class="w-28 rounded-md object-cover" src="{{ $tempUrl }}" alt="New photo"/>
            @elseif ($oldFile && ! $tempUrl)
                @if (is_string($oldFile))
                    <img class="w-28 rounded-md object-cover" src="{{ $oldFile }}" alt="Current photo"/>
                @else
                    <div class="w-24 rounded-md bg-gray-200 flex flex-col items-center justify-center">
                        <x-icons.image class="size-12"/>
                        <span class="text-xs text-secondary-medium">NO IMAGE</span>
                    </div>
                @endif
            @else
                <div class="w-24 rounded-md bg-gray-200 flex flex-col items-center justify-center">
                    <x-icons.image class="size-12"/>
                    <span class="text-xs text-secondary-medium">NO IMAGE</span>
                </div>
            @endif
        </div>
    @endif

    @if ($previewType === 'pdf')
        <div class="shrink-0">
            @if ($tempUrl)
                <a href="{{ $tempUrl }}" target="_blank" class="w-24 rounded-md bg-gray-200 flex flex-col items-center justify-center">
                    <x-icons.pdf-file />
                </a>
                <span class="text-xs text-secondary-medium text-center mt-2 w-24">
                    {{ $oldFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile ? ($oldFile->getClientOriginalName() ?? 'FILE') : ($fileName ?? 'FILE') }}
                </span>
            @elseif ($oldFile && is_string($oldFile))
                <a href="{{ $oldFile }}" target="_blank" class="flex flex-col justify-center">
                    <div class="w-24 rounded-md bg-gray-200 flex flex-col items-center justify-center">
                        <x-icons.pdf-file />
                    </div>
                    <span class="text-xs text-secondary-medium text-center mt-2 w-24">
                        {{ $fileName ?? 'NO FILE' }}
                    </span>
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
            'class' => 'block w-full text-sm text-slate-500 px-4 file:me-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-secondary-light file:text-white hover:file:bg-secondary-medium',
            'type' => 'file',
            'value' => $fileName
        ]) }}/>
        <x-error-span>{{ $errors->first($attributes->get('name')) }}</x-error-span>
    </div>
</div>
