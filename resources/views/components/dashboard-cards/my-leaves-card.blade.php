<div class="max-w-full h-full bg-white rounded-xl shadow-sm border p-2 border-gray-200 py-6 px-8 flex flex-col justify-between space-y-4">

    <div class="flex justify-between items-center">
        <h3 class="text-base font-semibold text-gray-600">
            {{ __('My Leaves') }}
        </h3>
        <button class="bg-[#C71C1C] hover:bg-[#A11919] text-white text-xs flex flex-row gap-2 font-medium px-3 py-2 rounded-md transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
             Leave Request
        </button>
    </div>

    <div class="grid grid-cols-2 items-center gap-6">

        <div class="flex items-end space-x-6">
            <div class="flex items-end -mb-3.5">
                <span class="text-[125px] font-extrabold text-secondary-medium leading-none">12</span>
                <span class="text-3xl text-secondary-medium font-semibold mb-2">/25</span>
            </div>

            <div class="border-l border-secondary-medium h-26"></div>

            <div class="flex flex-col justify-between min-w-full">
                <h4 class="text-secondary-medium font-bold text-md">
                    Annual Leave
                </h4>
                <div class="border-b border-3 border-primary w-6"></div>
                <ul class="mt-2 space-y-0.2 text-secondary-light text-xs leading-relaxed">
                    <li class="text-secondary-light">
                        <span>USED:</span>
                        <span ><strong class="text-secondary-dark">12</strong> days & <strong class="text-secondary-dark">4</strong> hours</span>
                    </li>
                    <li>
                        <span>REMAINING:</span> <strong class="text-secondary-dark">12</strong> days & <strong class="text-secondary-dark">4</strong> hours
                    </li>
                    <li>
                        <span> PENDING:</span> <strong class="text-secondary-dark">1</strong> day
                    </li>
                    <li>
                        <span> TOTAL:</span> <strong class="text-secondary-dark">25</strong> days
                    </li>
                </ul>
            </div>
        </div>


        <div class="flex flex-col items-end justify-end">
            <div class="relative w-56 h-40 -mb-2">
                <canvas id="leavesGaugeChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-end">
                    <p class="text-[#C71C1C] mb-3 -ml-6 text-4xl font-bold">
                        50 <span class="text-sm -ml-2">%</span>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const canvas = document.getElementById("leavesGaugeChart");
        const ctx = canvas.getContext("2d");
        const value = 50;

        new Chart(ctx, {
            type: "doughnut",
            data: {
                datasets: [{
                    data: [0],
                    backgroundColor: ["transparent"],
                }],
            },
            options: {
                cutout: "70%",
                circumference: 180,
                rotation: 270,
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { left: 15, right: 15, top: 5, bottom: 5 }
                },
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                animation: false,
            },
            plugins: [{
                id: 'customGauge',
                afterDraw(chart) {
                    const { width, height } = chart.chartArea;
                    const centerX = width / 2;
                    const centerY = height * 0.9;
                    const radius = Math.min(width, height * 2) / 2.25;
                    const thickness = radius * 0.24;
                    const startAngle = Math.PI;
                    const endAngle = 2 * Math.PI;
                    const percentAngle = startAngle + (value / 100) * Math.PI;

                    ctx.save();
                    ctx.lineWidth = thickness;
                    ctx.lineCap = "round";


                    ctx.strokeStyle = "#dcddde";
                    ctx.arc(centerX, centerY, radius, startAngle, endAngle);
                    ctx.stroke();


                    ctx.beginPath();
                    ctx.strokeStyle = "#C71C1C";
                    ctx.arc(centerX, centerY, radius, startAngle, percentAngle);
                    ctx.stroke();

                    ctx.restore();
                }
            }]
        });
    });
</script>
