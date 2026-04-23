<div wire:ignore class="w-full" x-data="{
    value: @entangle($attributes->wire('model')),
    initSelect2() {
        let select = $(this.$refs.select);

        select.select2({
            width: '100%',
            dir: document.documentElement.dir || 'rtl',
        });

        if (this.value) {
            select.val(this.value).trigger('change.select2');
        }

        select.on('change', (e) => {
            this.value = e.target.value;
        });

        this.$watch('value', (newValue) => {
            if (select.val() !== newValue) {
                select.val(newValue).trigger('change.select2');
            }
        });
    }
}" x-init="initSelect2()">
    
    <style>
        .select2-container--default .select2-selection--single {
            height: 37.6px !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.875rem !important;
            color: #111827 !important;
            background-color: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            display: flex !important;
            align-items: center !important;
            transition: all 0.15s ease-in-out !important;
        }


        .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding: 0 !important;
            line-height: normal !important;
            color: inherit !important;
            width: 100%;
        }


        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            display: none !important;
        }


        .select2-container--default .select2-selection--single .select2-selection__arrow {
            background-image: url("data:image/svg+xml,%3csvg aria-hidden='true' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 10 6'%3e %3cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 1 4 4 4-4'/%3e %3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-size: 0.75em 0.75em !important;
            height: 100% !important;
            width: 2.5rem !important; 
            top: 0 !important;
        }


        [dir="rtl"] .select2-container--default .select2-selection--single .select2-selection__arrow {
            left: 0 !important;
            right: auto !important;
            background-position: left 0.75rem center !important;
        }


        [dir="ltr"] .select2-container--default .select2-selection--single .select2-selection__arrow {
            right: 0 !important;
            left: auto !important;
            background-position: right 0.75rem center !important;
        }

 
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #be1f24 !important;
            box-shadow: 0 0 0 2px rgba(190, 31, 36, 0.2) !important;
            outline: none !important;
        }
    </style>
    <select x-ref="select" {{ $attributes->whereDoesntStartWith('wire:model') }}>
        {{ $slot }}
    </select>
</div>



