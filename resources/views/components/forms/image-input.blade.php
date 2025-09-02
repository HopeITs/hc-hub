@props(['item' => null])
<div
    x-data="{ preview: null }"
    x-init="
            @this.on('resetPreview', () => preview = null);
        "
>
    <label for="logo-upload" class="cursor-pointer relative block aspect-video w-4/12 rounded-sm overflow-hidden border-2 border-dashed border-gray-300 group hover:border-primary-400 transition">
        <input id="logo-upload" type="file" class="hidden"
               wire:model="item.logo"
               x-ref="logo"
               x-on:change="
                    const file = $refs.logo.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = e => preview = e.target.result;
                        reader.readAsDataURL(file);
                    }
                "
        >

        <div class="flex items-center justify-center h-full bg-gray-100 group-hover:bg-primary-50 transition">
            <template x-if="preview">
                <img :src="preview" class="object-cover w-full h-full rounded-sm" />
            </template>

        @isset ($item['logo'])
            <img src="{{ $logo->temporaryUrl() }}" class="object-cover w-full h-full rounded-sm" />
        @else(array_key_exists('id', $item->toArray()) && $item->getFirstMediaUrl('logo'))

            <img src="{{ $item->getFirstMediaUrl('logo') }}" class="object-cover w-full h-full rounded-full" />

        @endisset
        </div>
    </label>


</div>
