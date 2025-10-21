<div class="min-h-screen bg-gray-50 rounded-lg p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
        <div>
            @include('livewire.dashboards.Partials.employee-details-card')
        </div>

        <div>
            @include('livewire.dashboards.Partials.team-members-card')
        </div>

        <div>
            @include('livewire.dashboards.Partials.today-birthday-card')
        </div>

        <div>
            @include('livewire.dashboards.Partials.upcoming-birthday-card')
        </div>

        <div>
            @include('livewire.dashboards.Partials.notes-checkboxes-card')
        </div>

        <div>
            @include('livewire.dashboards.Partials.checklist-items-card')
        </div>

        <div>
            @include('livewire.dashboards.Partials.my-badges-card')
        </div>

        <div>
            @include('livewire.dashboards.Partials.news-card')
        </div>



        <div>
            @include('livewire.dashboards.Partials.pending-requests')
        </div>


        <div>
            @include('livewire.dashboards.Partials.upcoming-holidays-card')
        </div>

        <div>
            @include('livewire.dashboards.Partials.upcoming-leaves-card')
        </div>

        <div>
            @include('livewire.dashboards.Partials.my-leaves-card')
        </div>


    </div>
</div>
