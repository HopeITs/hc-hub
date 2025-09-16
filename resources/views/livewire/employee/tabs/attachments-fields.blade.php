<form wire:submit.prevent="saveAttachments" class="space-y-6">
    @foreach ($attachmentForm->attachments as $index => $attachment)
        <input type="hidden" wire:model="attachmentForm.attachments.{{ $index }}.id">

        <div wire:key="attachment-{{ $index }}" class="relative border-b pb-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="md:col-span-2 flex justify-end">
                    <x-primary-button type="button"
                        wire:click="removeAttachment({{ $index }}, {{ $attachment['id'] ?? 'null' }})"
                        title="@lang('general.remove')">
                        <x-icons.trash />
                    </x-primary-button>
                </div>

                <div>
                    <x-forms.input-label for="attachment_name_{{ $index }}">@lang('general.name')</x-forms.input-label>
                    <x-forms.input id="attachment_name_{{ $index }}" type="text"
                        wire:model.defer="attachmentForm.attachments.{{ $index }}.name" />
                    <x-error-span>{{ $errors->first("attachmentForm.attachments.$index.name") }}</x-error-span>
                </div>

                <div>
                    <x-forms.input-label for="attachment_file_{{ $index }}">@lang('general.file')</x-forms.input-label>
                    <x-forms.file-input wire:model="attachmentForm.attachments.{{ $index }}.file"
                        name="attachmentForm.attachments.{{ $index }}.file"
                        id="attachment_file_{{ $index }}" />
                    <x-error-span>{{ $errors->first("attachmentForm.attachments.$index.file") }}</x-error-span>
                </div>

            </div>
        </div>
    @endforeach

    <div class="flex justify-center">
        <x-primary-button type="button" wire:click="addAttachment">
            + @lang('general.add-new')
        </x-primary-button>
    </div>

    @if (method_exists($attachmentForm, 'hasEmployee'))
        <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
            <x-primary-button type="submit">@lang('general.save')</x-primary-button>
            <x-secondary-button :href="route('employees.index')">@lang('general.cancel')</x-secondary-button>
        </div>
    @else
        <div class="text-center text-gray-500 italic mt-4">
            @lang('general.save-disabled-until-employee-created')
        </div>
    @endif
</form>
