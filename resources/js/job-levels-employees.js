import * as am5 from "@amcharts/amcharts5";
import * as am5xy from "@amcharts/amcharts5/xy";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";

const dynamicLabels = window.jobLevelLabels || [];
const dynamicCounts = window.employeeCounts || [];

const chartData = dynamicLabels.map((label, index) => {
    const colorIndex = (index % 8) + 1;
    const colorVar = `--color-primary-${colorIndex * 100}`;
    const color = getComputedStyle(document.documentElement).getPropertyValue(colorVar.trim()) || "#dc2626";

    return {
        category: label,
        value: dynamicCounts[index],
        color: am5.color(color),
    };
});


if (document.getElementById("jobLevelsChart") && chartData.length > 0) {
    am5.ready(function () {
        const rootStyle = getComputedStyle(document.documentElement);
        const primaryColor1 = am5.color(rootStyle.getPropertyValue("--color-primary100").trim() || "#dc2626");
        const primaryColor2 = am5.color(rootStyle.getPropertyValue("--color-primary-200").trim() || "#dc2626");
        const primaryColor3 = am5.color(rootStyle.getPropertyValue("--color-primary-300").trim() || "#dc2626");
        const primaryColor4 = am5.color(rootStyle.getPropertyValue("--color-primary-400").trim() || "#dc2626");
        const primaryColor5 = am5.color(rootStyle.getPropertyValue("--color-primary-500").trim() || "#dc2626");
        const primaryColor6 = am5.color(rootStyle.getPropertyValue("--color-primary-600").trim() || "#dc2626");
        const primaryColor7 = am5.color(rootStyle.getPropertyValue("--color-primary-700").trim() || "#dc2626");
        const primaryColor8 = am5.color(rootStyle.getPropertyValue("--color-primary-800").trim() || "#dc2626");

        const root = am5.Root.new("jobLevelsChart");

        root.setThemes([am5themes_Animated.new(root)]);

        const chart = root.container.children.push(
            am5xy.XYChart.new(root, {
                panX: false,
                panY: false,
                wheelX: "none",
                wheelY: "none",
                layout: root.verticalLayout,
                paddingTop: 10,
                paddingBottom: 10,
                paddingLeft: 0,
                paddingRight: 10
            })
        );

        const data = chartData;

        const yRenderer = am5xy.AxisRendererY.new(root, {
            inversed: true,
            minGridDistance: 20,
            cellStartLocation: 0.1,
            cellEndLocation: 0.9,
        });
        yRenderer.labels.template.setAll({
            fontSize: 13,
            fill: am5.color(0x333333),
            oversizedBehavior: "wrap",
            textAlign: "left",
        });

        const yAxis = chart.yAxes.push(
            am5xy.CategoryAxis.new(root, {
                categoryField: "category",
                renderer: yRenderer,
            })
        );
        yAxis.data.setAll(data);

        const xRenderer = am5xy.AxisRendererX.new(root, {
            strokeOpacity: 0.1,
        });
        const xAxis = chart.xAxes.push(
            am5xy.ValueAxis.new(root, {
                renderer: xRenderer,
                min: 0,
            })
        );

        const series = chart.series.push(
            am5xy.ColumnSeries.new(root, {
                name: "Employees",
                xAxis: xAxis,
                yAxis: yAxis,
                valueXField: "value",
                categoryYField: "category",
                sequencedInterpolation: true,
                tooltip: am5.Tooltip.new(root, {
                    labelText: "{categoryY}: {valueX}",
                }),
            })
        );

        series.columns.template.setAll({
            height: am5.percent(60),
            cornerRadiusTR: 6,
            cornerRadiusBR: 6,
            strokeOpacity: 0,
        });
        series.columns.template.adapters.add("fill", (fill, target) => target.dataItem.dataContext.color);

        series.data.setAll(data);

        series.bullets.push(() =>
            am5.Bullet.new(root, {
                locationX: 0.5,
                sprite: am5.Label.new(root, {
                    text: "{valueX}",
                    fill: am5.color(0xffffff),
                    centerY: am5.percent(50),
                    centerX: am5.percent(50),
                    fontSize: 13,
                    populateText: true,
                }),
            })
        );

        series.appear(1000);
        chart.appear(1000, 100);

        root._logo?.dispose();
    });
}
