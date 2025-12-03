<div class="w-full h-full rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden p-6">
    <div class="flex w-full">
        <div class="text-[18px] font-semibold text-secondary-medium mb-4">@lang('general.contract-type-employees')  </div>
        <div class="ms-2"> <x-icons.chart/></div>
    </div>


    <div id="chartdiv" data-chart='@json($chartData)' class="w-full h-[200px] "></div>
</div>
