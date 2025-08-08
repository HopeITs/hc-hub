<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
            :title="__('general.projects-management')"
            :crumbs="[
['label' => __('general.projects-management')],
        ['label' => trans_choice('general.project',2) , 'url' => route('projects.index')] ,
        ['label' => $project->name]
    ]"
            :showButton="true"

        />

    </div>
    <div>
        <h1>{{$project->name}}</h1>
    </div>
</div>
