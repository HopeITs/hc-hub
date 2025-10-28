<div class="min-h-screen bg-gray-50 rounded-lg p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">

        <div>
            <x-dashboard-cards.leave-request-card/>
        </div>

        <div>
            <x-dashboard-cards.soon-ending-contracts-card/>
        </div>

        <div>
            <x-dashboard-cards.employee-chart-statistics/>
        </div>

        <div>
            <x-dashboard-cards.tree-chart-statistics/>
        </div>

        <div>
            <x-dashboard-cards.department-employees-statistics/>
        </div>

        <div>
            <x-dashboard-cards.contract-type-employees-statistics/>
        </div>

        <div>
            <x-dashboard-cards.job-level-employees-statistics/>
        </div>

        <div>
            <x-dashboard-cards.salary-per-employee-statistics/>
        </div>

        <div>
            <x-dashboard-cards.no-leaves-employees-statistics/>
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
            <livewire:dashboards.hr-notes-card :employee="auth()->id()"/>
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
