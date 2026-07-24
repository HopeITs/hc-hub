import Chart from "chart.js/auto";

function initSalaryTypeScatter() {
    const canvas = document.getElementById("salaryTypeMiniChart");
    if (!canvas) return;

    const seriesData = JSON.parse(canvas.dataset.series || "[]");

    if (canvas._chart) {
        canvas._chart.destroy();
    }

    const labels = seriesData.map(i => i.label);
    const values = seriesData.map(i => i.value);
    const colors = seriesData.map(i => i.color);

    const maxValue = Math.max(...values);
    const suggestedMax = maxValue === 0 ? 1 : maxValue + 10;

    const numericX = labels.map((_, i) => i + 1);

    const chart = new Chart(canvas, {
        type: "line",
        data: {
            labels: numericX,
            datasets: [
                {
                    data: numericX.map((x, i) => ({
                        x,
                        y: values[i]
                    })),
                    pointBackgroundColor: colors,
                    pointBorderColor: colors,
                    pointRadius: 2.8,
                    borderWidth: 0,
                    showLine: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,

            scales: {
                x: {
                    type: "linear",
                    min: 0.7,
                    max: numericX.length + 0.3,
                    grid: { display: false },
                    ticks: {
                        callback: function(value, index) {
                            return labels[index];
                        },
                        color: "#414042",
                        font: {
                            size: 10,
                            weight: "600"
                        }
                    }
                },

                y: {
                    beginAtZero: true,
                    suggestedMax,
                    grid: { color: "#e5e7eb" },
                    ticks: {
                        color: "#414042",
                        font: {
                            size: 10,
                            weight: "600"
                        }
                    }
                }
            },

            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.parsed.y} Employees`
                    }
                }
            }
        }
    });

    canvas._chart = chart;
}

document.addEventListener("DOMContentLoaded", initSalaryTypeScatter);
window.Livewire?.hook("message.processed", initSalaryTypeScatter);
