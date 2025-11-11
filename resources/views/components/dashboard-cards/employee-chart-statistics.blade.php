<div class="w-full h-[28rem] rounded-2xl bg-white border border-[var(--color-neutral-200)] shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
    <div class="flex justify-between items-center px-6 py-5 border-b border-[var(--color-neutral-200)] bg-gradient-to-r from-[var(--color-neutral-50)] to-white">
        <h2 class="text-xl font-semibold text-[var(--color-neutral-800)] tracking-tight">Employee Statistics</h2>
        <select
            class="text-sm border border-[var(--color-neutral-200)] rounded-lg px-3 py-1.5 text-[var(--color-neutral-700)] bg-white shadow-sm hover:bg-[var(--color-neutral-50)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-300)] transition">
            <option>Monthly</option>
            <option>Quarterly</option>
            <option>Yearly</option>
        </select>
    </div>

    <div class="grid grid-cols-3 gap-4 px-6 py-5 bg-[var(--color-neutral-50)]">
        <div class="flex flex-col items-center justify-center rounded-xl bg-white shadow-sm p-3 border border-[var(--color-neutral-200)]">
            <p class="text-xs uppercase tracking-wide text-[var(--color-neutral-600)]">New</p>
            <p id="stat-new" class="text-2xl font-bold text-[var(--color-primary-500)] mt-1">0</p>
        </div>
        <div class="flex flex-col items-center justify-center rounded-xl bg-white shadow-sm p-3 border border-[var(--color-neutral-200)]">
            <p class="text-xs uppercase tracking-wide text-[var(--color-neutral-600)]">Ended</p>
            <p id="stat-ended" class="text-2xl font-bold text-[var(--color-secondary-medium)] mt-1">0</p>
        </div>
        <div class="flex flex-col items-center justify-center rounded-xl bg-white shadow-sm p-3 border border-[var(--color-neutral-200)]">
            <p class="text-xs uppercase tracking-wide text-[var(--color-neutral-600)]">Terminated</p>
            <p id="stat-terminated" class="text-2xl font-bold text-neutral-300 mt-1">0</p>
        </div>
    </div>

    <div class="relative px-6 pb-6 h-[calc(100%-12rem)]">
        <canvas id="employeeChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", async () => {
        const el = document.getElementById("employeeChart");
        const select = document.querySelector("select");
        if (!el) return;

        const ctx = el.getContext("2d");

        if (Chart && Chart.Filler) {
            Chart.register(Chart.Filler);
        }

        const primary = getComputedStyle(document.documentElement).getPropertyValue("--color-primary-500").trim();
        const primaryLight = getComputedStyle(document.documentElement).getPropertyValue("--color-primary-300").trim();
        const secondary = getComputedStyle(document.documentElement).getPropertyValue("--color-secondary-medium").trim();
        const neutral = getComputedStyle(document.documentElement).getPropertyValue("--color-neutral-300").trim();

        const gradientPrimary = ctx.createLinearGradient(0, 0, 0, 400);
        gradientPrimary.addColorStop(0, `${primaryLight}80`);
        gradientPrimary.addColorStop(1, `${primaryLight}00`);

        const gradientSecondary = ctx.createLinearGradient(0, 0, 0, 400);
        gradientSecondary.addColorStop(0, `${secondary}60`);
        gradientSecondary.addColorStop(1, `${secondary}00`);

        const gradientNeutral = ctx.createLinearGradient(0, 0, 0, 400);
        gradientNeutral.addColorStop(0, `${neutral}60`);
        gradientNeutral.addColorStop(1, `${neutral}00`);

        async function fetchData(mode = "monthly") {
            const res = await fetch(`/employee-statistics/${mode}`);
            return res.json();
        }

        const data = await fetchData("monthly");

        document.getElementById("stat-new").textContent = `+${data.new.at(-1)}`;
        document.getElementById("stat-ended").textContent = data.ended.at(-1);
        document.getElementById("stat-terminated").textContent = data.terminated.at(-1);

        const chart = new Chart(ctx, {
            type: "line",
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: "New Employees",
                        data: data.new,
                        borderColor: primary,
                        backgroundColor: gradientPrimary,
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2.5,
                        pointBackgroundColor: primary,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: "Ended",
                        data: data.ended,
                        borderColor: secondary,
                        backgroundColor: gradientSecondary,
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2.5,
                        pointBackgroundColor: secondary,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: "Terminated",
                        data: data.terminated,
                        borderColor: neutral,
                        backgroundColor: gradientNeutral,
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2.5,
                        pointBackgroundColor: neutral,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: "index", intersect: false },
                plugins: {
                    legend: {
                        position: "top",
                        align: "end",
                        labels: {
                            usePointStyle: true,
                            color: getComputedStyle(document.documentElement).getPropertyValue("--color-neutral-700"),
                            padding: 15,
                            font: { size: 13, family: "SomarSans" }
                        }
                    },
                    tooltip: {
                        backgroundColor: "#fff",
                        titleColor: getComputedStyle(document.documentElement).getPropertyValue("--color-neutral-900"),
                        bodyColor: getComputedStyle(document.documentElement).getPropertyValue("--color-neutral-700"),
                        borderColor: getComputedStyle(document.documentElement).getPropertyValue("--color-neutral-200"),
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        boxPadding: 6,
                        displayColors: true,
                        usePointStyle: true
                    }
                },
                scales: {
                    x: {
                        grid: { color: "rgba(220,221,222,0.5)", drawBorder: false },
                        ticks: {
                            color: getComputedStyle(document.documentElement).getPropertyValue("--color-neutral-600"),
                            font: { size: 13, family: "Lato" }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: "rgba(220,221,222,0.5)", drawBorder: false },
                        ticks: {
                            color: getComputedStyle(document.documentElement).getPropertyValue("--color-neutral-600"),
                            font: { size: 13, family: "Lato" },
                            padding: 8
                        }
                    }
                }
            }
        });

        select.addEventListener("change", async (e) => {
            const mode = e.target.value.toLowerCase();
            const data = await fetchData(mode);

            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.new;
            chart.data.datasets[1].data = data.ended;
            chart.data.datasets[2].data = data.terminated;

            document.getElementById("stat-new").textContent = `+${data.new.at(-1)}`;
            document.getElementById("stat-ended").textContent = data.ended.at(-1);
            document.getElementById("stat-terminated").textContent = data.terminated.at(-1);

            chart.update();
        });
    });
</script>
