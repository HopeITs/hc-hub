<div class="min-h-screen bg-gray-50 rounded-lg p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
        <div>
            @include('livewire.dashboards.employee-dashboard.cards.employee-details-card')
        </div>

        <div>
            @include('livewire.dashboards.employee-dashboard.cards.my-leaves-card')
        </div>

        <div>
            @include('livewire.dashboards.employee-dashboard.cards.team-members-card')
        </div>

        <div>
            @include('livewire.dashboards.employee-dashboard.cards.today-birthday-card')
        </div>

        <div>
            @include('livewire.dashboards.employee-dashboard.cards.upcoming-birthday-card')
        </div>

        <div>
            <livewire:dashboards.employee-notes-card :employee="auth()->id()"/>
        </div>

        <div>
            @include('livewire.dashboards.employee-dashboard.cards.checklist-items-card')
        </div>

        <div>
            @include('livewire.dashboards.employee-dashboard.cards.my-badges-card')
        </div>

        <div>
            @include('livewire.dashboards.employee-dashboard.cards.pending-requests-card')
        </div>


        <div>
            @include('livewire.dashboards.employee-dashboard.cards.upcoming-holidays-card')
        </div>

        <div>
            @include('livewire.dashboards.employee-dashboard.cards.upcoming-leaves-card')
        </div>


        <div>
            @include('livewire.dashboards.employee-dashboard.cards.news-card')
        </div>

    </div>
</div>
