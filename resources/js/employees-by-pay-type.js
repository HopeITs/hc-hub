import * as am5 from "@amcharts/amcharts5";
import * as am5percent from "@amcharts/amcharts5/percent";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";

function initEmployeesByPayType() {
    const chartId = "salaryTypePieChart";
    const legendId = "salaryTypeLegend";
    const host = document.getElementById(chartId);
    if (!host) return;

    // Clean up any previous chart instance
    am5.registry.rootElements
        .filter(r => r.dom.id === chartId)
        .forEach(r => r.dispose());

    // Create chart root
    const root = am5.Root.new(chartId);
    root.setThemes([am5themes_Animated.new(root)]);

    // Read CSS variables or use fallbacks
    const css = getComputedStyle(document.documentElement);
    const colorMonth = am5.color(css.getPropertyValue("--color-primary-500").trim() || "#dc2626");
    const colorHour = am5.color(css.getPropertyValue("--color-secondary-medium").trim() || "#f87171");
    const colorSession = am5.color(css.getPropertyValue("--color-neutral-300").trim() || "#d1d5db");

    // Create chart
    const chart = root.container.children.push(
        am5percent.PieChart.new(root, {
            innerRadius: am5.percent(70),
            layout: root.verticalLayout
        })
    );

    // Create series
    const series = chart.series.push(
        am5percent.PieSeries.new(root, {
            valueField: "value",
            categoryField: "category"
        })
    );

    // Basic styling
    series.slices.template.setAll({
        strokeOpacity: 0,
        tooltipText: "{category}: {value}",
        templateField: "sliceSettings"
    });
    series.labels.template.set("forceHidden", true);
    series.ticks.template.set("forceHidden", true);

    // Data
    series.data.setAll([
        { category: "Per Month", value: 55 , sliceSettings: { fill: colorMonth } },
        { category: "Per Hour", value: 15, sliceSettings: { fill: colorHour } },
        { category: "Per Session", value: 30, sliceSettings: { fill: colorSession } }
    ]);

    // Center text
    chart.seriesContainer.children.push(
        am5.Label.new(root, {
            text: "160",
            fontSize: 36,
            fontWeight: "700",
            centerX: am5.p50,
            centerY: am5.percent(65),
            fill: am5.color("#1f2937")
        })
    );
    chart.seriesContainer.children.push(
        am5.Label.new(root, {
            text: "All Employees",
            fontSize: 13,
            centerX: am5.p50,
            centerY: am5.percent(62),
            dy: 24,
            fill: am5.color("#6b7280")
        })
    );

    // Legend
    const legend = chart.children.push(
        am5.Legend.new(root, {
            centerX: am5.percent(50),
            x: am5.percent(50),
            marginTop: 15
        })
    );
    root._logo?.dispose();

    legend.data.setAll(series.dataItems);

    // Add legend inside your custom div
    const legendDiv = document.getElementById(legendId);
    if (legendDiv) legendDiv.appendChild(legend.dom);

    // Animate
    series.appear(800, 100);
    chart.appear(800, 100);

     // Remove amCharts watermark
}
document.addEventListener("DOMContentLoaded", initEmployeesByPayType);
window.Livewire?.hook?.("message.processed", initEmployeesByPayType);
