import * as am5 from "@amcharts/amcharts5";
import * as am5xy from "@amcharts/amcharts5/xy";
import * as am5percent from "@amcharts/amcharts5/percent";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";

window.renderContractTypeEmployeesChart = function (data, themeColor = "#dc2626") {
    if (!data || data.length === 0) return;

    am5.ready(function () {
        const root = am5.Root.new("chartdiv");
        root.setThemes([am5themes_Animated.new(root)]);

        const color = am5.color(themeColor);

        let container = root.container.children.push(
            am5.Container.new(root, {
                width: am5.p100,
                height: am5.p100,
                layout: root.verticalLayout,
                paddingTop: 10
            })
        );

        let pieChart = container.children.push(
            am5percent.PieChart.new(root, {
                width: am5.percent(60),
                height: am5.percent(50),
                innerRadius: am5.percent(50),
                align: "center",
                centerX: am5.percent(-30),
            })
        );

        let pieSeries = pieChart.series.push(
            am5percent.PieSeries.new(root, {
                valueField: "value",
                categoryField: "category"
            })
        );

        pieSeries.labels.template.set("forceHidden", true);
        pieSeries.ticks.template.set("forceHidden", true);

        pieSeries.slices.template.setAll({
            strokeOpacity: 0,
            tooltipText: "{category}: {value}"
        });

        // Pie labels
        let label1 = pieChart.seriesContainer.children.push(
            am5.Label.new(root, {
                text: "",
                fontSize: 35,
                fontWeight: "bold",
                centerX: am5.p50,
                centerY: am5.p50
            })
        );

        let label2 = pieChart.seriesContainer.children.push(
            am5.Label.new(root, {
                text: "",
                fontSize: 12,
                centerX: am5.p50,
                centerY: am5.p50,
                dy: 30
            })
        );

        let currentSlice = null;

        // Update on click
        pieSeries.slices.template.events.on("pointerover", function (ev) {
            const slice = ev.target;
            const dataItem = slice.dataItem;
            const color = slice.get("fill");

            label1.setAll({
                fill: color,
                text: root.numberFormatter.format(dataItem.get("valuePercentTotal"), "#.'%'")
            });

            label2.set("text", dataItem.get("category"));

            columnSeries.columns.template.setAll({
                fill: color,
                stroke: color
            });

            columnSeries.data.setAll(dataItem.dataContext.breakdown);
            yAxis.data.setAll(dataItem.dataContext.breakdown);
        });

        pieSeries.data.setAll(data);

        // ✅ Auto select first slice
        pieSeries.events.on("datavalidated", function () {
            let firstSlice = pieSeries.slices.getIndex(0);
            firstSlice.events.dispatch("pointerover", { type: "pointerover", target: firstSlice });
        });

        // ----------------------
        // Column chart
        // ----------------------
        let columnChart = container.children.push(
            am5xy.XYChart.new(root, {
                width: am5.p100,
                height: am5.percent(45),
                panX: false,
                panY: false,
                wheelX: "none",
                wheelY: "none"
            })
        );

        let yRenderer = am5xy.AxisRendererY.new(root, { minGridDistance: 20 });
        let yAxis = columnChart.yAxes.push(
            am5xy.CategoryAxis.new(root, {
                categoryField: "category",
                renderer: yRenderer
            })
        );

        let xAxis = columnChart.xAxes.push(
            am5xy.ValueAxis.new(root, {
                renderer: am5xy.AxisRendererX.new(root, { strokeOpacity: 0.1 })
            })
        );

        let columnSeries = columnChart.series.push(
            am5xy.ColumnSeries.new(root, {
                xAxis,
                yAxis,
                valueXField: "value",
                categoryYField: "category",
                clustered: false
            })
        );

        columnSeries.columns.template.setAll({
            height: am5.percent(40),
            fill: color,
            stroke: color,
            tooltipText: "{categoryY}: {valueX}"
        });

        root._logo?.dispose();
    });
};
