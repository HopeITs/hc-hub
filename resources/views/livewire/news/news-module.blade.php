<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs :title="__('general.news')" :crumbs="[['label' => __('general.news-management')]]" :showButton="true" />
        <x-primary-button href="{{ route('news.create') }}">@lang('general.add-new')</x-primary-button>
    </div>

    <livewire:news.news-table />
</div>
