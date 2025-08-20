@props([
    'previewType' => 'image', // image - docs - pdf - video
    'oldFile' => null,
])

<div class="grid md:grid-cols-2 items-end gap-4">
    <input
        {{ $attributes->merge([
            'class' => 'w-full h-fit text-sm rounded-lg cursor-pointer rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition',
            'type' => 'file',
        ]) }}
    />

    @if ($previewType === 'image')
        @php
            // Handle both TemporaryUploadedFile (new upload) and string URL (existing logo)
            $src = null;
            if (is_object($oldFile) && method_exists($oldFile, 'temporaryUrl')) {
                $src = $oldFile->temporaryUrl();
            } elseif (is_string($oldFile) && $oldFile !== '') {
                $src = $oldFile;
            }
        @endphp

        @if ($src)
            <a href="{{ $src }}" target="_blank" rel="noopener">
                <img src="{{ $src }}" alt="preview-img" class="h-20 p-1 rounded-md" />
            </a>
        @endif
    @endif
</div>
