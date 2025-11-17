@php
    $computedWorkEmailUsername = $infoForm->username ?? '';
@endphp
<form wire:submit.prevent="saveInformation" class="space-y-6">
    <div class="grid md:grid-cols-2 gap-x-6 gap-y-3 border-b pb-8 mb-8">
        <x-heading-label>@lang('general.employment_info')</x-heading-label>

        <div>
            <div class="flex justify-between">
                <x-forms.input-label for="username">@lang('general.work_email')</x-forms.input-label>
                <span class="text-secondary-light text-xs">password is: HopeCenterHR</span>
            </div>
            <div class="flex rtl:flex-row-reverse">
                <x-forms.input type="text" wire:model="infoForm.username" id="username" class="rounded-r-none"
                    placeholder="username" />
                <span
                    class="inline-flex items-center px-3 ltr:rounded-e-md rtl:rounded-s-md shadow-md border ltr:border-s-0 rtl:border-e-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                    @hcsyria.org
                </span>
            </div>
            <x-error-span>{{ $errors->first('infoForm.username') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="work_phone">@lang('general.work_phone')</x-forms.input-label>
            <x-forms.input type="tel" wire:model="infoForm.work_phone" id="work_phone" />
            <x-error-span>{{ $errors->first('infoForm.work_phone') }}</x-error-span>
        </div>

        <div class="col-span-full">
            <x-forms.input-label for="roles">{{ trans_choice('general.role', 2) }}</x-forms.input-label>
            <input class="hidden" wire:model="infoForm.roles" id="roles_hidden">
            <x-forms.input class="tagify" id="roles" value="{{ $infoForm->roles }}"
                data-whitelist="{{ implode(',', $roles) }}" />
            <x-error-span>{{ $errors->first('infoForm.roles') }}</x-error-span>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-x-6 gap-y-3 border-b pb-8 mb-8">
        <x-heading-label>@lang('general.contact_info')</x-heading-label>

        <div>
            <x-forms.input-label for="personal_phone">@lang('general.personal_phone')</x-forms.input-label>
            <x-forms.input type="tel" wire:model="infoForm.personal_phone" id="personal_phone" />
            <x-error-span>{{ $errors->first('infoForm.personal_phone') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="personal_email">@lang('general.personal_email')</x-forms.input-label>
            <x-forms.input type="email" wire:model="infoForm.personal_email" id="personal_email" />
            <x-error-span>{{ $errors->first('infoForm.personal_email') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="whatsapp_work">WhatsApp (work)</x-forms.input-label>
            <x-forms.input type="text" wire:model="infoForm.whatsapp_work" id="whatsapp_work" />
            <x-error-span>{{ $errors->first('infoForm.whatsapp_work') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="telegram_work">Telegram (work)</x-forms.input-label>
            <x-forms.input type="text" wire:model="infoForm.telegram_work" id="telegram_work" />
            <x-error-span>{{ $errors->first('infoForm.telegram_work') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="facebook">Facebook</x-forms.input-label>
            <x-forms.input type="url" wire:model="infoForm.facebook" id="facebook"
                placeholder="https://www.facebook.com/your-page" />
            <x-error-span>{{ $errors->first('infoForm.facebook') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="instagram">Instagram</x-forms.input-label>
            <x-forms.input type="url" wire:model="infoForm.instagram" id="instagram"
                placeholder="https://www.instagram.com/your-handle" />
            <x-error-span>{{ $errors->first('infoForm.instagram') }}</x-error-span>
        </div>

        <div class="md:col-span-2">
            <x-forms.input-label for="linkedin">LinkedIn</x-forms.input-label>
            <x-forms.input type="url" wire:model="infoForm.linkedin" id="linkedin"
                placeholder="https://www.linkedin.com/in/your-profile" />
            <x-error-span>{{ $errors->first('infoForm.linkedin') }}</x-error-span>
        </div>
    </div>

    <div class="flex justify-end space-x-2">
        <x-primary-button type="submit" class="!w-auto">@lang('general.save')</x-primary-button>
        <x-secondary-button :href="route('employees.index')" class="!w-auto">@lang('general.cancel')</x-secondary-button>
    </div>
</form>
