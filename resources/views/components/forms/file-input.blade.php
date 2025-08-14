@props([
    'previewType' => 'image' ,// image - docs - pdf - video
    'file' => null
])
<div class="grid md:grid-cols-2 items-end gap-4">
    <input {{$attributes->merge([
    'class' => 'w-full h-fit text-sm  rounded-lg cursor-pointer focus:outline-none rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition',
    'type' => 'file'
])}}
    />


    @if($previewType === 'image' && $file !== "" && isset($file))

        <a href="{{$file->temporaryUrl()}}" >
            <img src="{{$file->temporaryUrl()}}" alt="preview-img"
                 class="h-20 p-1 rounded-md  "/>
        </a>

    @endif
</div>

