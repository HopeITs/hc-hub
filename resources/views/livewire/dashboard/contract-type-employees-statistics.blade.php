<div class="w-full bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden transition-all">
    <div class="flex justify-between items-center px-6 py-4 bg-neutral-50">
        <h3 class="text-lg font-semibold text-secondary-dark tracking-wide">Contract Type Employees</h3>
        <span class="text-sm bg-secondary-dark/20 px-3 py-1 rounded-full text-secondary-dark font-medium">2025</span>
    </div>

    <div class="p-6 flex flex-col items-center justify-center">
        <div
            id="chartdiv"
            class="w-full h-[460px] bg-gray-50 rounded-xl"
            x-data="{ chartData: {{ $statistics->toJson() }} }"
            x-init="$nextTick(() => window.renderContractTypeEmployeesChart(chartData, '#dc2626'))"
        ></div>
    </div>
</div>
