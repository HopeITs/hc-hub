<div class="w-full h-full rounded-surface bg-white border border-neutral-200 shadow-e1 hover:shadow-e2 transition-all duration-300 p-6 flex flex-col">

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-h4 font-semibold text-secondary-medium flex items-center gap-2">
            @lang('general.employees-statistics')
            <x-icons.chart class="w-5 h-5 mb-1 text-primary"/>
        </h2>

        <select
            class="text-small border border-neutral-300 rounded-surface px-4 py-2 bg-white shadow-e1 text-neutral-700">
            <option value="monthly">@lang('general.monthly')</option>
            <option value="quarterly">@lang('general.quarterly')</option>
            <option value="yearly">@lang('general.yearly')</option>
        </select>
    </div>

    <div class=" max-h-[125px]">
        <canvas id="employeeChart"></canvas>
    </div>

    <div id="chartLegend" class="flex items-center gap-6 mt-6 text-body font-medium ms-3 text-neutral-600"></div>

</div>

<script src="{{ asset('js/chart.umd.js') }}"></script>

<script>
    document.addEventListener("DOMContentLoaded", async () => {

        const canvas = document.getElementById("employeeChart");
        const select = document.querySelector("select");
        if (!canvas) return;
        if (typeof Chart === "undefined") return console.error("Chart.js not loaded.");

        const ctx = canvas.getContext("2d");

        const styles = getComputedStyle(document.documentElement);
        const primary   = styles.getPropertyValue("--color-primary").trim();
        const secondary = styles.getPropertyValue("--color-secondary-medium").trim();
        const neutral   = styles.getPropertyValue("--color-neutral-300").trim();

        async function fetchData(mode = "monthly") {
            const res = await fetch(`/employee-statistics/${mode}`);
            if (!res.ok) return { labels: [], new: [], ended: [], terminated: [] };
            return res.json();
        }

        let data = await fetchData("monthly");

        const chart = new Chart(ctx, {
            type: "line",
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: "New Employees",
                        data: data.new,
                        borderColor: primary,
                        fill: false,
                        tension: 0.3,
                        borderWidth: 0.7,
                        pointBackgroundColor: primary,
                        pointRadius: 2.1,
                        pointHoverRadius: 4.2
                    },
                    {
                        label: "Ended",
                        data: data.ended,
                        borderColor: secondary,
                        fill: false,
                        tension: 0.3,
                        borderWidth: 0.7,
                        pointBackgroundColor: secondary,
                        pointRadius: 2.1,
                        pointHoverRadius: 4.2
                    },
                    {
                        label: "Terminated",
                        data: data.terminated,
                        borderColor: neutral,
                        fill: false,
                        tension: 0.3,
                        borderWidth: 0.7,
                        pointBackgroundColor: neutral,
                        pointRadius: 2.1,
                        pointHoverRadius: 4.2
                    },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        mode: "index",
                        intersect: false,
                        backgroundColor: "#fff",
                        titleColor: "#111",
                        bodyColor: "#444",
                        borderColor: "rgba(0,0,0,0.1)",
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 6,
                        displayColors: true
                    }

                },
                scales: {
                    x: {
                        grid: {
                            color: "rgba(220,221,222,0.5)",
                            drawBorder: false,
                            drawOnChartArea: true
                        },
                        ticks: {
                            color: styles.getPropertyValue("--color-secondary-dark"),
                            font: {
                                size: 12,
                                weight: "bold",
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "rgba(220,221,222,0.5)",
                            drawBorder: false,
                            drawOnChartArea: true
                        },
                        ticks: {
                            stepSize: 1,
                            color: styles.getPropertyValue("--color-secondary-dark"),
                            font: {
                                size: 12,
                                weight: "bold",
                            },
                            callback: value => Number.isInteger(value) ? value : ""
                        }
                    }
                }
            }
        });

        function renderLegend(chartInstance) {
            const legendBox = document.getElementById("chartLegend");
            legendBox.innerHTML = "";

            chartInstance.data.datasets.forEach((ds, index) => {
                const item = document.createElement("div");
                item.className = "flex items-center gap-1 cursor-pointer select-none";

                const isHidden = chartInstance.isDatasetVisible(index) === false;

                const dot = document.createElement("span");
                dot.className = "w-2.5 h-2.5 rounded-full inline-block";
                dot.style.backgroundColor = isHidden ? "transparent" : ds.borderColor;
                dot.style.border = `2px solid ${ds.borderColor}`;

                const label = document.createElement("span");
                label.textContent = ds.label;
                label.className = "text-small";
                label.style.opacity = isHidden ? 0.4 : 1;

                item.appendChild(dot);
                item.appendChild(label);

                item.addEventListener("click", () => {
                    const meta = chartInstance.getDatasetMeta(index);

                    meta.hidden = meta.hidden === null ? !chartInstance.data.datasets[index].hidden : null;

                    chartInstance.update();
                    renderLegend(chartInstance);
                });

                legendBox.appendChild(item);
            });
        }


        renderLegend(chart);

        select.addEventListener("change", async e => {
            const mode = e.target.value;
            const newData = await fetchData(mode);

            chart.data.labels = newData.labels;
            chart.data.datasets[0].data = newData.new;
            chart.data.datasets[1].data = newData.ended;
            chart.data.datasets[2].data = newData.terminated;

            chart.update();
            renderLegend(chart);
        });
    });
</script>
