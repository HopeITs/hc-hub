<form wire:submit.prevent="saveAttachments" class="space-y-6">

    @foreach ($attachmentForm->attachments as $index => $attachment)

        <input type="hidden"
               wire:model="attachmentForm.attachments.{{ $index }}.id">

        <div wire:key="attachment-{{ $index }}"
             class="relative border-b pb-6 mb-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="md:col-span-2 flex justify-end">
                    <x-primary-button type="button" :showSpinner="false"
                        wire:click="removeAttachment({{ $index }}, {{ $attachment['id'] ?? 'null' }})">
                        <x-icons.trash />
                    </x-primary-button>
                </div>

                <div>
                    <x-forms.input-label>@lang('general.name')</x-forms.input-label>
                    <x-forms.input type="text"
                        wire:model.defer="attachmentForm.attachments.{{ $index }}.name" />
                    @error("attachmentForm.attachments.{$index}.name") <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-forms.input-label>@lang('general.category')</x-forms.input-label>
                    <x-forms.select
                        wire:model="attachmentForm.attachments.{{ $index }}.category"
                        :options="\App\Enums\AttachmentCategory::options()"
                        placeholder="{{ __('general.choose') }}"
                    />
                    @error("attachmentForm.attachments.{$index}.category") <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-forms.input-label>@lang('general.file')</x-forms.input-label>
                    <x-forms.file-input
                        wire:model="attachmentForm.attachments.{{ $index }}.file"
                        preview-type="pdf"
                        :old-file="$attachment['old_file_url'] ?? null"
                        :file-name="$attachment['file_name'] ?? null"
                        accept=".pdf, .jpg, .jpeg, .png"
                    />
                    @error("attachmentForm.attachments.{$index}.file") <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

            </div>
        </div>

    @endforeach

    <div class="flex justify-center">
        <x-primary-button type="button" :showSpinner="false"
                          wire:click="addAttachment">
            + @lang('general.add-new')
        </x-primary-button>
    </div>

    <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
        <x-primary-button type="submit">@lang('general.save')</x-primary-button>
        <x-secondary-button :href="route('employees.index')">@lang('general.cancel')</x-secondary-button>
    </div>
</form>
