@use('Livewire\Features\SupportFileUploads\TemporaryUploadedFile')

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <x-forms.input-label for="photo">@lang('general.photo')</x-forms.input-label>
        <div class="mt-2 flex items-center space-x-6">
            <div class="shrink-0">
                @include('livewire.employee.partials.photo-upload')
            </div>
            <div>
                <input type="file" wire:model.live="form.photo" id="photo"
                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100" />
                <x-error-span>{{ $errors->first('form.photo') }}</x-error-span>
            </div>
        </div>
    </div>

    @include('livewire.employee.partials.form-fields')
</div>
