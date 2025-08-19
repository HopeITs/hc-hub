@props([
    'previewType' => 'image',
    'oldFile' => null,
    'newFile' => null,
])

<div class="grid md:grid-cols-2 items-end gap-4">
    <input
        {{ $attributes->merge([
            'class' =>
                'w-full h-fit text-sm rounded-lg cursor-pointer focus:outline-none border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition',
            'type' => 'file',
        ]) }} />

    @if ($previewType === 'image')
        @if ($newFile && method_exists($newFile, 'temporaryUrl'))
            <a href="{{ $newFile->temporaryUrl() }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $newFile->temporaryUrl() }}" alt="New file preview"
                    class="h-20 p-1 rounded-md border border-gray-200 object-cover" />
            </a>
        @elseif($oldFile)
            <a href="{{ $oldFile }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $oldFile }}" alt="Existing file preview"
                    class="h-20 p-1 rounded-md border border-gray-200 object-cover" />
            </a>
        @endif
    @endif
</div>
