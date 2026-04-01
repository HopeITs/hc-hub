<div class="w-full h-full bg-white rounded-[32px] shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] border border-gray-200 px-6 py-3 flex flex-col">

    <div class="flex items-center gap-4 mt-3">
        <h3 class="text-[18px] font-semibold text-secondary-medium">
            @lang('general.employees-per-salary-type')
        </h3>

        <x-icons.chart class="w-5 mb-1 h-5 text-primary"/>
    </div>

    <div class="grid grid-cols-2 -mt-3 items-center">

        <div class="flex flex-col items-center  pt-6 justify-center">
            <div
                id="salaryTypePieChart"
                class="w-[130px] me-8 pt-2 h-[130px]"
                data-series='@json($series)'
                data-percentage="{{ $highlightPercentage }}"
            ></div>

            <div class="flex flex-row justify-center items-center  ms-40 gap-8 mt-10">
                @foreach($series as $item)
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full"
                              style="background: {{ $item['color'] }}"></span>
                        <span class="text-neutral-700 font-normal whitespace-nowrap text-[11px]">
                            {{ $item['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="w-full -ms-3">
            <canvas
                id="salaryTypeMiniChart"
                class="w-full h-[140px]"
                data-series='@json($series)'
            ></canvas>
        </div>

    </div>

</div>
