<div class="w-full bg-white rounded-2xl shadow border border-[var(--color-neutral-200)] overflow-hidden">
    <!-- Header -->
    <div class="flex justify-between items-center px-6 py-4 bg-[var(--color-primary)]">
        <h3 class="text-lg font-semibold text-white">
            {{ __('My Leaves Overview') }}
        </h3>
        <span class="text-sm bg-white/20 px-3 py-1 rounded-full text-white font-medium">
            2025
        </span>
    </div>

    <!-- Chart Section -->
    <div class="p-6 flex flex-col items-center justify-center">
        <div class="relative w-40 h-40">
            <canvas id="leavesDonutChart"></canvas>
            <!-- Center label -->
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <p class="text-xs text-[var(--color-neutral-500)]">Used</p>
                <p class="text-xl font-semibold text-[var(--color-primary-600)]">12</p>
                <p class="text-xs text-[var(--color-neutral-500)]">of 24 Days</p>
            </div>
        </div>

        <!-- Legend -->
        <div class="mt-4 flex flex-wrap justify-center gap-4 text-sm">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-[var(--color-primary-500)]"></span>
                <span class="text-[var(--color-neutral-700)]">Used</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-[var(--color-primary-200)]"></span>
                <span class="text-[var(--color-neutral-700)]">Remaining</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-[var(--color-neutral-300)]"></span>
                <span class="text-[var(--color-neutral-700)]">Pending</span>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('leavesDonutChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Used', 'Remaining', 'Pending'],
                datasets: [{
                    data: [12, 10, 2],
                    backgroundColor: [
                        getComputedStyle(document.documentElement).getPropertyValue('--color-primary-500').trim(),
                        getComputedStyle(document.documentElement).getPropertyValue('--color-primary-200').trim(),
                        getComputedStyle(document.documentElement).getPropertyValue('--color-neutral-300').trim()
                    ],
                    borderWidth: 2,
                    borderColor: '#fff',
                    cutout: '70%'
                }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                },
                maintainAspectRatio: false
            }
        });
    });
</script>
