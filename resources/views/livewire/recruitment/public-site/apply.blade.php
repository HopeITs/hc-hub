<x-slot:title>
    {{ $jobPostVersion->title_en ?? __('recruitment.public_apply_title') }}
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
                    <h2 class="text-xl font-semibold text-gray-900">{{ __('recruitment.public_apply_thanks_title') }}</h2>
                    <p class="text-body text-secondary-medium">{{ __('recruitment.public_apply_thanks_body') }}</p>
                </div>
            @else
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
                        <x-forms.input-label for="full_name" required :value="__('recruitment.public_apply_full_name')" />
                        <x-forms.input id="full_name" model="full_name" required />
                        <x-forms.input-error field="full_name" input-id="full_name" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-forms.input-label for="email" required :value="__('recruitment.public_apply_email')" />
                            <x-forms.input id="email" type="email" model="email" required />
                            <x-forms.input-error field="email" input-id="email" />
                        </div>

                        <div>
                            <x-forms.input-label for="phone" :value="__('recruitment.public_apply_phone')" />
                            <x-forms.input id="phone" type="tel" model="phone" />
                            <x-forms.input-error field="phone" input-id="phone" />
                        </div>
                    </div>

                    <div>
                        <x-forms.input-label for="city_id" :value="__('recruitment.public_apply_city')" />
                        <select id="city_id" wire:model="city_id" class="w-full py-2 px-3 text-body text-neutral-900 rounded-control border border-neutral-300 bg-white shadow-e1 focus:outline-none focus:ring-2 focus:ring-secondary-light focus:border-primary transition">
                            <option value="">{{ __('recruitment.choose') }}</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}">{{ $city->{transColumn('name')} }}</option>
                            @endforeach
                        </select>
                    </div>

                    @foreach ($fields as $field)
                        @php
                            $key = $field['key'] ?? null;
                            $label = app()->getLocale() === 'ar'
                                ? ($field['label_ar'] ?? $field['label'] ?? $key)
                                : ($field['label_en'] ?? $field['label'] ?? $key);
                            $type = $field['type'] ?? 'text';
                            $required = ! empty($field['required']);
                        @endphp
                        @if ($key)
                            <div>
                                <x-forms.input-label :for="$key" :required="$required" :value="$label" />

                                @if ($type === 'textarea')
                                    <x-forms.textarea :id="$key" wire:model="answers.{{ $key }}" />
                                @elseif ($type === 'select' && ! empty($field['options']))
                                    <select id="{{ $key }}" wire:model="answers.{{ $key }}" class="w-full py-2 px-3 text-body text-neutral-900 rounded-control border border-neutral-300 bg-white shadow-e1 focus:outline-none focus:ring-2 focus:ring-secondary-light focus:border-primary transition">
                                        <option value="">{{ __('recruitment.choose') }}</option>
                                        @foreach ($field['options'] as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <x-forms.input :id="$key" :type="$type === 'email' || $type === 'tel' ? $type : 'text'" wire:model="answers.{{ $key }}" />
                                @endif

                                <x-forms.input-error :field="'answers.'.$key" :input-id="$key" />
                            </div>
                        @endif
                    @endforeach

                    <div>
                        <x-forms.checkbox-input type="checkbox" id="privacy_accepted" wire:model="privacy_accepted">
                            {{ __('recruitment.public_apply_privacy_consent') }}
                        </x-forms.checkbox-input>
                        <x-forms.input-error field="privacy_accepted" input-id="privacy_accepted" />
                    </div>

                    <x-primary-button class="w-full" type="submit">
                        {{ __('recruitment.public_apply_submit') }}
                    </x-primary-button>
                </form>
            @endif

        </x-auth-card>
    </div>
</section>
