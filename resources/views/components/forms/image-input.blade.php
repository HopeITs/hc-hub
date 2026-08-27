@props(['item' => null])
<div x-data="{ preview: null }" x-init="@this.on('resetPreview', () => preview = null);">
    <label for="logo-upload"
        class="cursor-pointer relative block aspect-video w-4/12 rounded-inset overflow-hidden border-2 border-dashed border-neutral-300 group hover:border-secondary-medium transition">
        <input id="logo-upload" type="file" class="hidden" wire:model="item.logo" x-ref="logo"
            x-on:change="
                    const file = $refs.logo.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = e => preview = e.target.result;
                        reader.readAsDataURL(file);
                    }
                ">

        <div class="flex items-center justify-center h-full bg-neutral-100 group-hover:bg-secondary-soft transition">
            <template x-if="preview">
                <img :src="preview" alt="{{ __('general.a11y.logo-preview') }}"
                     class="object-fit w-full h-full rounded-inset" />
            </template>

            @isset($item['logo'])
                <img src="{{ $logo->temporaryUrl() }}" alt="{{ __('general.a11y.logo-preview') }}"
                     class="object-fit w-full h-full rounded-inset" />
            @else(array_key_exists('id', $item->toArray()) && $item->getFirstMediaUrl('logo'))
                <img src="{{ $item->getFirstMediaUrl('logo') }}" alt="{{ __('general.a11y.current-logo') }}"
                     class="object-fit w-full h-full rounded-full" />
            @endisset
        </div>
    </label>


</div>
