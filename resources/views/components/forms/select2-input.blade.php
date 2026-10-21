@props(['disabled' => false, 'hideSearch' => false])

<div class="w-full" 
    x-data="{
        value: @entangle($attributes->wire('model')),
        initSelect2() {
            let select = $(this.$refs.select);

            // Inside a modal, render the dropdown into the modal root: on <body>
            // it would sit outside the dialog's focus trap and its search box
            // could never keep focus.
            let modalRoot = $(this.$el).closest('[data-modal-root]').children('[data-modal-dropdowns]');

            select.select2({
                width: '100%',
                dropdownParent: modalRoot.length ? modalRoot : $(document.body),
                dir: document.documentElement.dir || 'rtl',
                minimumResultsForSearch: {{ $hideSearch ? 'Infinity' : 0 }}, // hide search only when hideSearch is set
                templateSelection: function (data) {
                    // This prevents long text from breaking the layout
                    return data.text;
                }
            });


            select.val(this.value).trigger('change.select2');

            // select2 names its combobox only by the current value (e.g. Select),
            // so the field's own <label for> never reaches a screen reader.
            // Name it by the label first, then the value.
            let label = select.attr('id') && document.querySelector(`label[for='${select.attr('id')}']`);
            let selection = select.next('.select2').find('.select2-selection');
            if (label && selection.length) {
                label.id ||= `${select.attr('id')}-label`;
                let rendered = selection.find('.select2-selection__rendered').attr('id');
                selection.attr('aria-labelledby', [label.id, rendered].filter(Boolean).join(' '));
            }
            

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