<div class="min-h-screen bg-gray-50 rounded-lg p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">

        <div>
            <x-dashboard-cards.leave-request-card/>
        </div>

        <div>
            <x-dashboard-cards.soon-ending-contracts-card/>
        </div>

        <div>
            <x-dashboard-cards.upcoming-leaves-card/>
        </div>

        <div>
            <x-dashboard-cards.news-card/>
        </div>

        <div>
            <x-dashboard-cards.upcoming-holidays-card/>
        </div>

        <div>
            <livewire:dashboards.manager-notes-card :employee="auth()->id()"/>
        </div>

        <div>
            <x-dashboard-cards.team-members-card/>
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
            <x-dashboard-cards.my-badges-card
                :myBadges="$myBadges"/>
        </div>

    </div>
</div>
