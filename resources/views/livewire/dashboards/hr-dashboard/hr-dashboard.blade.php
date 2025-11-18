<div class="min-h-screen bg-gray-50 rounded-lg p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">

        <div>
            <x-dashboard-cards.leave-request-card/>
        </div>

        <div>
            <livewire:dashboard.soon-ending-contracts-card />
        </div>


        <div>
            <x-dashboard-cards.employee-chart-statistics/>
        </div>

        <div>
            <livewire:dashboard.department-employee-statistics />
        </div>

        <div>
            <x-dashboard-cards.no-leaves-employees-statistics/>
        </div>

        <div>
            <livewire:dashboard.contract-type-employees-statistics/>
        </div>

        <div>
            <livewire:dashboard.job-level-employee-statistics/>
        </div>

        <div>
            <x-dashboard-cards.salary-per-employee-statistics/>
        </div>


    </div>
</div>
