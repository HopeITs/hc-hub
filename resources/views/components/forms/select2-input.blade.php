{{-- <div wire:ignore class="w-full" x-data="{
    value: @entangle($attributes->wire('model')),
    initSelect2() {
        let select = $(this.$refs.select);

        select.select2({
            width: '100%',
            dir: document.documentElement.dir || 'rtl',
            templateSelection: function (data) {
                return data.text;
            }
        });

        if (this.value) {
            select.val(this.value).trigger('change.select2');
        }

        select.on('change', (e) => {
            this.value = e.target.value;
        });

        // Remove title (tooltip) on selection
        select.on('select2:select', () => {
            $('.select2-selection__rendered').removeAttr('title');
        });

        this.$watch('value', (newValue) => {
            if (select.val() !== newValue) {
                select.val(newValue).trigger('change.select2');
            }
        });

        // Initial title removal
        $('.select2-selection__rendered').removeAttr('title');
    }
}" x-init="initSelect2()">
    <select x-ref="select" {{ $attributes->whereDoesntStartWith('wire:model') }}>
        {{ $slot }}
    </select>
</div> --}}











@props(['disabled' => false])

<div class="w-full" 
    x-data="{
        value: @entangle($attributes->wire('model')),
        initSelect2() {
            let select = $(this.$refs.select);

            select.select2({
                width: '100%',
                dir: document.documentElement.dir || 'rtl',
                templateSelection: function (data) {
                    // This prevents long text from breaking the layout
                    return data.text;
                }
            });


            select.val(this.value).trigger('change.select2');
            

            select.prop('disabled', {{ $disabled ? 'true' : 'false' }});


            select.on('change', (e) => {
                this.value = e.target.value;
            });

            this.$watch('value', (newValue) => {
                if (select.val() !== newValue) {
                    select.val(newValue).trigger('change.select2');
                }
            });


            select.on('select2:select', () => {
                $('.select2-selection__rendered').removeAttr('title');
            });
            $('.select2-selection__rendered').removeAttr('title');
        }
    }" 
    x-init="initSelect2()"
>
    <div wire:ignore class="w-full">
        <select x-ref="select" {{ $attributes->whereDoesntStartWith('wire:model')->merge(['disabled' => $disabled]) }}>
            {{ $slot }}
        </select>
    </div>
</div>