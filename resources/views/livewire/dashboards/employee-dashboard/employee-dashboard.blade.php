<div class="min-h-screen bg-gray-50 rounded-lg p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">

        <div>
            <x-dashboard-cards.employee-details-card
                :employee="$employee"
                :positionTitle="$positionTitle"
                :departmentName="$departmentName"
                :joinedOn="$joinedOn"
                :workEmail="$workEmail"/>
        </div>

        <div>
            <x-dashboard-cards.my-leaves-card
                :employee="$employee"
                :contract="$contract"/>
        </div>

        <div>
            <x-dashboard-cards.team-members-card
                :contract="$contract"
                :employee="$employee"/>
        </div>

        <div>
            <x-dashboard-cards.today-birthday-card
                :todayBirthdays="$todayBirthdays"/>
        </div>

        <div>
            <x-dashboard-cards.upcoming-birthday-card
                :upcomingBirthdays="$upcomingBirthdays"/>
        </div>

        <div>
            <livewire:dashboards.employee-notes-card :employee="auth()->id()"/>
        </div>

        <div>
            <x-dashboard-cards.checklist-items-card
                :deliveredItems="$deliveredItems"/>
        </div>

        <div>
            <x-dashboard-cards.my-badges-card
                :myBadges="$myBadges"/>
        </div>

        <div>
            <x-dashboard-cards.pending-requests-card
                :pendingRequests="$pendingRequests"/>
        </div>

        <div>
            <x-dashboard-cards.upcoming-holidays-card/>
        </div>

        <div>
            <x-dashboard-cards.upcoming-leaves-card/>
        </div>

        <div>
            <x-dashboard-cards.news-card/>
        </div>

    </div>
</div>
