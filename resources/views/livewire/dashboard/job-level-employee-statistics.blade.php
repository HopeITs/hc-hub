<div
    class="w-full h-full rounded-3xl bg-white border border-neutral-200 shadow-sm hover:shadow-md transition-all duration-300 p-6 flex flex-col">

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-[18px] font-semibold text-secondary-medium flex items-center gap-2">
            Employees per Job Level
            <x-icons.document-text class="w-5 mb-1 h-5 text-primary"/>
        </h2>

    </div>

    <div class="relative flex-1 min-h-[200px]">
        <canvas id="jobLevelEmployeesChart" class="w-full h-full"></canvas>
    </div>

</div>

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", () => {

            const ctx = document.getElementById("jobLevelEmployeesChart");
            if (!ctx) return;

            const jobLevels = @json($jobLevelLabels);
            const employees = @json($employeeCounts);

            const primaryColor = "#be1f24";
            const gridColor    = "#dcddde";

            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: jobLevels,
                    datasets: [
                        {

                            data: employees,
                            backgroundColor: primaryColor,
                            borderColor: primaryColor,
                            borderWidth: 0.05,
                            borderRadius: 6,
                            barPercentage: 0.06,
                            categoryPercentage: 0.25
                        },

                        {

                            type: "line",
                            data: employees,
                            borderWidth: 0,
                            pointBackgroundColor: primaryColor,
                            pointBorderColor: primaryColor,
                            pointRadius: 3.6,
                            pointHoverRadius: 6,
                            tension: 0
                        }
                    ]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    plugins: {
                        legend: { display: false },

                        tooltip: {
                            enabled: true,
                            backgroundColor: "#1f2937",
                            titleColor: "#fff",
                            bodyColor: "#fff",
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label: (ctx) => `${ctx.parsed.y} Employees`
                            }
                        }
                    },

                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: "#6b7280",
                                font: { size: 12, family: "SomarSans" },
                                padding: 10
                            }
                        },

                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: "#6b7280",
                                font: { size: 12, family: "SomarSans" },
                                padding: 6,
                                callback: (value) =>
                                    Number.isInteger(value) ? value : null
                            }
                        }
                    }
                }

            });

        });
    </script>
@endpush
