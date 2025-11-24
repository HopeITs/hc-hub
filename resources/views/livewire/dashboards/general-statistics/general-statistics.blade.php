
<div class="space-y-10">
        <div class="grid grid-cols-3 gap-6">

            <div class="col-span-2 h-68">
                <livewire:dashboard.department-employee-statistics />
            </div>

            <div class="col-start-3 h-68">
                <livewire:dashboard.salary-per-employee-statistics />
            </div>

            <div class="col-start-1 row-start-2 h-68">
                <x-dashboard-cards.employee-chart-statistics/>
            </div>

            <div class="col-span-2 col-start-2 row-start-2 h-68">
                <livewire:dashboard.job-level-employee-statistics/>
            </div>

            <div class="col-start-1 row-start-3 h-68">
                <livewire:dashboard.soon-ending-contracts-card />
            </div>

            <div class="col-start-2 row-start-3  h-68">
                <x-dashboard-cards.no-leaves-employees-statistics/>
            </div>

            <div class="col-start-3 row-start-3 h-68">
                <livewire:dashboard.contract-type-employees-statistics/>
            </div>

        </div>

</div>
