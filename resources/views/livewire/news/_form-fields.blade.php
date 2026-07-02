<div class="grid grid-cols-2 gap-4">
    <div class="col-span-2">
        <x-forms.input-label for="title" required>@lang('general.title')</x-forms.input-label>
        <x-forms.input id="title" type="text" wire:model.live="form.title" />
        <x-error-span>{{ $errors->first('form.title') }}</x-error-span>
    </div>

    <div class="col-span-2">
        <x-forms.input-label for="description" required>@lang('general.description')</x-forms.input-label>
        <x-forms.textarea id="description" wire:model.live="form.description" />
        <x-error-span>{{ $errors->first('form.description') }}</x-error-span>
    </div>

    <div class="col-span-2 sm:col-span-1">
        <x-forms.input-label>@lang('general.target_type')</x-forms.input-label>
        <x-forms.select2-input wire:model.live="form.newsable_type">
            <option value="">@lang('general.all_org')</option>
            <option value="{{ \App\Models\City::class }}">{{ trans_choice('general.city', 1) }}</option>
            <option value="{{ \App\Models\Center::class }}">{{ trans_choice('general.center', 1) }}</option>
            <option value="{{ \App\Models\Program::class }}">{{ trans_choice('general.program', 1) }}</option>
            <option value="{{ \App\Models\Employee::class }}">{{ trans_choice('general.employee', 1) }}</option>
        </x-forms.select2-input>
        <x-error-span>{{ $errors->first('form.newsable_type') }}</x-error-span>
    </div>

    @if (!empty($form->newsable_type))
        <div class="col-span-2 sm:col-span-1" wire:key="entity-select-{{ $form->newsable_type }}">
            <x-forms.input-label>@lang('general.target_entity')</x-forms.input-label>
            <x-forms.select2-input wire:model.live="form.newsable_id">
                <option value="">@lang('general.select_entity')</option>
                @foreach ($this->form->entities() as $entity)
                    <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                @endforeach
            </x-forms.select2-input>
            <x-error-span>{{ $errors->first('form.newsable_id') }}</x-error-span>
        </div>
    @endif

    <div class="col-span-2">
        <x-forms.input-label>@lang('general.photo')</x-forms.input-label>
        <x-forms.file-input wire:model.live="form.photo" name="form.photo" previewType="image"
            :oldFile="$form->photo ?: ($news?->getFirstMediaUrl('news-image') ?: null)"
            :fileName="$form->photo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                ? $form->photo->getClientOriginalName() ?? null
                : $news?->getFirstMedia('news-image')?->file_name"
            accept=".jpg,.jpeg,.png,.gif,.webp" />
        <x-error-span>{{ $errors->first('form.photo') }}</x-error-span>
    </div>

    <div class="col-span-2 sm:col-span-1">
        <x-forms.input-label>@lang('general.display_until')</x-forms.input-label>
        <x-forms.input type="date" wire:model.live="form.display_until" />
        <x-error-span>{{ $errors->first('form.display_until') }}</x-error-span>
    </div>

    <div class="col-span-2 sm:col-span-1">
        <x-forms.input-label>@lang('general.event_date')</x-forms.input-label>
        <x-forms.input type="date" wire:model.live="form.event_date" />
        <x-error-span>{{ $errors->first('form.event_date') }}</x-error-span>
    </div>
</div>
