<div class="w-full h-full rounded-2xl border border-gray-200 bg-white shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] overflow-hidden p-6">
    <div class="flex w-full">
        <div class="text-[18px] font-semibold text-secondary-medium mb-4">@lang('general.contract-type-employees')  </div>
        <div class="ms-2"> <x-icons.chart/></div>
    </div>


    <div id="chartdiv" data-chart='@json($chartData)' class="w-full h-[200px] "></div>
</div>
