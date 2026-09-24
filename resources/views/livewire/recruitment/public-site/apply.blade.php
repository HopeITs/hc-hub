<x-slot:title>
    {{ $jobPostVersion->title_en ?? __('recruitment-applicants.public_apply_title') }}
</x-slot:title>

<section class="min-h-[86.7dvh] rtl:text-right bg-neutral-100 flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-2xl">

        <div class="mb-6 flex items-center justify-between">
            <x-app-logo class="w-40" />
            <div class="text-body text-secondary-medium space-x-2 rtl:space-x-reverse">
                <a href="?lang=en" class="hover:underline {{ app()->getLocale() === 'en' ? 'font-semibold text-primary' : '' }}">EN</a>
                <span>/</span>
                <a href="?lang=ar" class="hover:underline {{ app()->getLocale() === 'ar' ? 'font-semibold text-primary' : '' }}">AR</a>
            </div>
        </div>

        <x-auth-card class="space-y-6">

            @if ($jobPostVersion)
                <div class="space-y-2 border-b border-neutral-200 pb-4">
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ app()->getLocale() === 'ar' ? $jobPostVersion->title_ar : $jobPostVersion->title_en }}
                    </h1>
                    @if ($jobPostVersion->body_en || $jobPostVersion->body_ar)
                        <div class="text-body text-secondary-medium whitespace-pre-line">
                            {{ app()->getLocale() === 'ar' ? $jobPostVersion->body_ar : $jobPostVersion->body_en }}
                        </div>
                    @endif
                </div>
            @endif

            @if ($submitted)
                <div class="text-center py-8 space-y-2">
                    <h2 class="text-xl font-semibold text-gray-900">{{ __('recruitment-applicants.public_apply_thanks_title') }}</h2>
                    <p class="text-body text-secondary-medium">{{ __('recruitment-applicants.public_apply_thanks_body') }}</p>
                </div>
            @else
                @if ($errors->any())
                    {{-- Most of the form sits below the fold; without this a
                         failed submit looks like nothing happened. --}}
                    <p role="alert" class="rounded-control border border-primary bg-primary/5 p-3 text-primary text-body">
                        {{ __('recruitment-applicants.public_apply_fix_errors') }}
                    </p>
                @endif
                @error('token')
                    <p role="alert" class="text-primary text-body">{{ $message }}</p>
                @enderror
                @error('email')
                    {{-- Business-rule rejections (already applied / invitation
                         required) key on "email" in the submission service, not
                         a form field the validator itself touched. --}}
                    <p role="alert" class="text-primary text-body">{{ $message }}</p>
                @enderror

                <form wire:submit="submit" class="space-y-4">

                    <div>
                        <x-forms.input-label for="full_name" required :value="__('recruitment-applicants.public_apply_full_name')" />
                        <x-forms.input id="full_name" model="full_name" required />
                        <x-forms.input-error field="full_name" input-id="full_name" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-forms.input-label for="email" required :value="__('recruitment-applicants.public_apply_email')" />
                            <x-forms.input id="email" type="email" model="email" required />
                            <x-forms.input-error field="email" input-id="email" />
                        </div>

                        <div>
                            <x-forms.input-label for="phone" :value="__('recruitment-applicants.public_apply_phone')" />
                            <x-forms.input id="phone" type="tel" model="phone" />
                            <x-forms.input-error field="phone" input-id="phone" />
                        </div>
                    </div>

                    <div wire:key="public-apply-city">
                        <x-forms.input-label for="city_id" :value="__('recruitment-applicants.public_apply_city')" />
                        <x-forms.select2-input wire:model="city_id" id="city_id">
                            <option value="">{{ __('recruitment.choose') }}</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}">{{ $city->{transColumn('name')} }}</option>
                            @endforeach
                        </x-forms.select2-input>
                    </div>

                    {{-- $fields come from Team 1's normalizeFields(): labels and
                         option labels are {en, ar}; options are {value, label}. --}}
                    @foreach ($fields as $field)
                        @php
                            $key = $field['key'];
                            $locale = app()->getLocale();
                            $label = $field['label'][$locale] ?: ($field['label']['en'] ?: $key);
                            $type = $field['type'];
                        @endphp
                        <div wire:key="public-apply-answer-{{ $key }}">
                            <x-forms.input-label :for="$key" :required="$field['required']" :value="$label" />

                            @if ($type === 'file')
                                <input
                                    type="file"
                                    id="{{ $key }}"
                                    wire:model="uploads.{{ $key }}"
                                    accept="{{ collect($field['accept'])->map(fn ($extension) => '.'.$extension)->implode(',') }}"
                                    @if ($field['multiple']) multiple @endif
                                    class="block w-full text-body text-neutral-900 file:me-3 file:rounded-control file:border-0 file:bg-primary file:px-3 file:py-2 file:text-white"
                                />
                                @if ($field['help'][$locale] ?? '')
                                    <p class="mt-1 text-body text-secondary-medium">{{ $field['help'][$locale] }}</p>
                                @endif
                                <div wire:loading wire:target="uploads.{{ $key }}" class="mt-1 text-body text-secondary-medium">{{ __('recruitment-applicants.public_apply_uploading') }}</div>
                                <x-forms.input-error :field="'uploads.'.$key" :input-id="$key" />
                                @foreach ($errors->get('uploads.'.$key.'.*') as $messages)
                                    <p class="text-primary text-body">{{ $messages[0] }}</p>
                                @endforeach
                            @elseif ($type === 'checkbox')
                                <x-forms.checkbox-input type="checkbox" :id="$key" wire:model="answers.{{ $key }}" />
                            @elseif ($type === 'textarea')
                                <x-forms.textarea :id="$key" wire:model="answers.{{ $key }}" />
                            @elseif (in_array($type, ['select', 'radio'], true) && $field['options'] !== [])
                                <x-forms.select2-input wire:model="answers.{{ $key }}" :id="$key">
                                    <option value="">{{ __('recruitment.choose') }}</option>
                                    @foreach ($field['options'] as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'][$locale] ?: ($option['label']['en'] ?: $option['value']) }}</option>
                                    @endforeach
                                </x-forms.select2-input>
                            @else
                                <x-forms.input :id="$key" :type="in_array($type, ['email', 'tel', 'date', 'number', 'url'], true) ? $type : 'text'" wire:model="answers.{{ $key }}" />
                            @endif

                            <x-forms.input-error :field="'answers.'.$key" :input-id="$key" />
                        </div>
                    @endforeach

                    <div>
                        <x-forms.checkbox-input type="checkbox" id="privacy_accepted" wire:model="privacy_accepted">
                            {{ __('recruitment-applicants.public_apply_privacy_consent') }}
                        </x-forms.checkbox-input>
                        <x-forms.input-error field="privacy_accepted" input-id="privacy_accepted" />
                    </div>

                    <x-primary-button class="w-full" type="submit" wire:loading.attr="disabled">
                        {{ __('recruitment-applicants.public_apply_submit') }}
                    </x-primary-button>
                </form>
            @endif

        </x-auth-card>
    </div>
</section>
