<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.course-category', 2)"
            :crumbs="[
                ['label' => __('general.project-basics')],
                ['label' => trans_choice('general.course-category', 2), 'route' => route('course-categories.index')],
            ]"
            :showButton="false"
        />
        <div>
            <x-primary-button wire:click="$emit('create')">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>

    <livewire:basics.course-category-table />
</div>
