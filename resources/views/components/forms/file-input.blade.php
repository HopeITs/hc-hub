@props([
    'previewType' => 'image' ,// image - docs - pdf - video
    'file' => null
])

<input {{$attributes->merge([
    'class' => 'w-full text-sm  rounded-lg cursor-pointer focus:outline-none rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition',
    'type' => 'file'
])}}
/>


@if($previewType === 'image' && $file !== "" && isset($file))

        <a href="{{$file->temporaryUrl()}}">
            <img src="{{$file->temporaryUrl()}}" alt="preview-img"
                 class="aspect-auto p-1 rounded-md  border-1 border-gray-400 border-dashed"/>
        </a>

@endif
