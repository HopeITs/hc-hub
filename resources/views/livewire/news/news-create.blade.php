<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs :title="__('general.news')" :crumbs="[['label' => __('general.news-management')], ['label' => $news ? __('general.edit') : __('general.add-new')]]" />
    </div>

    <form wire:submit.prevent="save" class="w-full space-y-4">
        @include('livewire.news._form-fields')

        <div class="flex justify-end gap-2">
            <x-primary-button type="submit">@lang('general.save')</x-primary-button>
            <x-primary-button type="button" class="!bg-gray-200 !text-black hover:!bg-gray-300"
                onclick="window.location='{{ route('news.index') }}'">
                @lang('general.cancel')
            </x-primary-button>
        </div>
    </form>
</div>
