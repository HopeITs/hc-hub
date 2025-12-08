<div class="space-y-10">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Employees and Departments -->
        <div class="h-68 md:col-span-2">
            <livewire:dashboard.department-employee-statistics />
        </div>

        <!-- Employees by Pay Type -->
        <div class="h-68 md:col-start-3">
            <livewire:dashboard.salary-per-employee-statistics />
        </div>

        <!-- Employee Statistics -->
        <div class="h-68 md:col-start-1 md:row-start-2">
            <x-dashboard-cards.employee-chart-statistics/>
        </div>

        <!-- Employees per Job Level -->
        <div class="h-68 md:col-span-2 md:col-start-2 md:row-start-2">
            <livewire:dashboard.job-level-employee-statistics/>
        </div>

        <!-- Contracts Ending Soon -->
        <div class="h-68 md:col-start-1 md:row-start-3">
            <livewire:dashboard.soon-ending-contracts-card />
        </div>

        <!-- No Leaves Employees -->
        <div class="h-68 md:col-start-2 md:row-start-3">
            <livewire:dashboard.no-leaves-employees-statistics/>
        </div>

        <!-- Contract Type Employees -->
        <div class="h-68 md:col-start-3 md:row-start-3">
            <livewire:dashboard.contract-type-employees-statistics/>
        </div>

    </div>

</div>
