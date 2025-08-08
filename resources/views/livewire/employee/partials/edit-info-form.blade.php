@use('Livewire\Features\SupportFileUploads\TemporaryUploadedFile')

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <x-forms.input-label for="photo">@lang('general.photo')</x-forms.input-label>
        <div class="mt-2 flex items-center space-x-6">
            <div class="shrink-0">
                @if ($form->photo instanceof TemporaryUploadedFile)
                    <img class="h-20 w-20 rounded-full object-cover" src="{{ $form->photo->temporaryUrl() }}"
                        alt="New profile photo" />
                @elseif ($form->employee?->photo)
                    <img class="h-20 w-20 rounded-full object-cover"
                        src="{{ asset('storage/' . $form->employee->photo) }}" alt="Current profile photo" />
                @else
                    <div class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center">
                        <svg class="h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                @endif
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
