import '@yaireo/tagify/dist/tagify.css'
import '../css/app.css';

    import {
        Chart,DoughnutController, BarController, LineController, BarElement, LineElement,ArcElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Title
            } from 'chart.js';
    Chart.register(DoughnutController,BarController, LineController, BarElement, LineElement,ArcElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Title );

    import Swal from 'sweetalert2'

    import 'flowbite';

    import './password-toggle.js'
    import './password-strength.js'

    import Tagify from '@yaireo/tagify'

    import * as am5 from "@amcharts/amcharts5";
    import * as am5xy from "@amcharts/amcharts5/xy";
    import * as am5percent from "@amcharts/amcharts5/percent";
    import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";

    window.Swal = Swal
    window.Chart = Chart;

document.querySelectorAll('.tagify').forEach((el) => {
    const whitelist = el.dataset.whitelist ? el.dataset.whitelist.split(',') : []
    const enforceWhitelist = el.dataset.enforcewhitelist !== "0"

    const tagify = new Tagify(el, {
        whitelist,
        enforceWhitelist,
        dropdown: {

            enabled: 0,

        }

    })

    const hidden = document.getElementById(el.id + '_hidden')

    // preload existing values from Livewire into Tagify
    if (hidden.value) {
        try {
            const values = JSON.parse(hidden.value)
            tagify.addTags(values)
        } catch (e) {
            // if backend sent CSV instead of JSON
            tagify.addTags(hidden.value.split(','))
        }
    }

    function sync() {
        const values = tagify.value.map(t => t.value)
        hidden.value = JSON.stringify(values)
        hidden.dispatchEvent(new Event('input', { bubbles: true }))
    }

    tagify.on('add', sync)
    tagify.on('remove', sync)
    tagify.on('edit', sync)
})
am5.ready(function() {

    const rootStyle = getComputedStyle(document.documentElement);
    const primaryColor = am5.color(rootStyle.getPropertyValue("--color-primary").trim() || "#2563eb");
    const secondaryColor = am5.color(rootStyle.getPropertyValue("--color-secondary-medium").trim() || "#fbbf24");
    const neutralColor = am5.color(rootStyle.getPropertyValue("--color-neutral-300").trim() || "#9ca3af");
// Set up data
    var data = [{
        category: "Annual",
        value: 60,
        sliceSettings: {
            fill: primaryColor,
        },
        breakdown: [{
            category: "Full Time Annual",
            value: 40
        }, {
            category: "Part Time Annual",
            value: 20
        }]
    }, {
        category: "Service Agreement",
        value: 25,
        sliceSettings: {
            fill: secondaryColor,
        },
        breakdown: [{
            category: "Onsite Service Agreement",
            value: 15
        }, {
            category: "Online Service Agreement",
            value: 10
        }]
    }, {
        category: "Trial Period",
        value: 15,
        sliceSettings: {
            fill: neutralColor,
        },
        breakdown: [{
            category: "Full Time Trial Period",
            value: 10
        }, {
            category: "Part Time Trial Period",
            value: 5
        }]
    }]

// https://www.amcharts.com/docs/v5/getting-started/#Root_element
    var root = am5.Root.new("chartdiv");
    root.setThemes([am5themes_Animated.new(root)]);

// Create wrapper container
    var container = root.container.children.push(
        am5.Container.new(root, {
            width: am5.p100,
            height: am5.p100,
            layout: root.verticalLayout,
            paddingTop: 10
        })
    );


// ==============================================
// Column chart
// ==============================================

// Create chart
// https://www.amcharts.com/docs/v5/charts/xy-chart/
    var columnChart = container.children.push(
        am5xy.XYChart.new(root, {
            width: am5.p100,
            height: am5.percent(45),
            panX: false,
            panY: false,
            wheelX: "none",
            wheelY: "none"
        })
    );

// Create axes
// https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
    var yRenderer = am5xy.AxisRendererY.new(root, { minGridDistance: 20 });
    var yAxis = columnChart.yAxes.push(
        am5xy.CategoryAxis.new(root, {
            categoryField: "category",
            renderer: yRenderer
        })
    );

    yRenderer.grid.template.setAll({ location: 1, strokeOpacity: 0.05 });


    var xAxis = columnChart.xAxes.push(
        am5xy.ValueAxis.new(root, {
            renderer: am5xy.AxisRendererX.new(root, { strokeOpacity: 0.1 })
        })
    );


// Add series
// https://www.amcharts.com/docs/v5/charts/xy-chart/series/
    var columnSeries = columnChart.series.push(
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
        fill: primaryColor,
        stroke: primaryColor,
        tooltipText: "{categoryY}: {valueX}"
    });

//series.data.setAll(data);

// Make stuff animate on load
// https://www.amcharts.com/docs/v5/concepts/animations/
    columnChart.appear(1000, 100);


// ==============================================
// Column chart
// ==============================================

    var pieChart = container.children.push(
        am5percent.PieChart.new(root, {
            width: am5.percent(60),
            height: am5.percent(50),
            innerRadius: am5.percent(50),
            align: "center",
            centerX: am5.percent(-30),

        })
    );

// Create series
    var pieSeries = pieChart.series.push(
        am5percent.PieSeries.new(root, {
            valueField: "value",
            categoryField: "category"
        })
    );

    pieSeries.slices.template.setAll({
        templateField: "sliceSettings",
        strokeOpacity: 0
    });

    var currentSlice;
    pieSeries.slices.template.on("active", function(active, slice) {
        if (currentSlice && currentSlice != slice && active) {
            currentSlice.set("active", false)
        }

        var color = slice.get("fill");

        label1.setAll({
            fill: color,
            text: root.numberFormatter.format(slice.dataItem.get("valuePercentTotal"), "#.'%'")
        });

        label2.set("text", slice.dataItem.get("category"));

        columnSeries.columns.template.setAll({
            fill: slice.get("fill"),
            stroke: slice.get("fill")
        });

        columnSeries.data.setAll(slice.dataItem.dataContext.breakdown);
        yAxis.data.setAll(slice.dataItem.dataContext.breakdown);

        currentSlice = slice;
    });

    pieSeries.labels.template.set("forceHidden", true);
    pieSeries.ticks.template.set("forceHidden", true);

    pieSeries.data.setAll(data);

// Add label
    var label1 = pieChart.seriesContainer.children.push(am5.Label.new(root, {
        text: "",
        fontSize: 35,
        fontweight: "bold",
        centerX: am5.p50,
        centerY: am5.p50
    }));

    var label2 = pieChart.seriesContainer.children.push(am5.Label.new(root, {
        text: "",
        fontSize: 12,
        centerX: am5.p50,
        centerY: am5.p50,
        dy: 30
    }));

// Pre-select first slice
    pieSeries.events.on("datavalidated", function() {
        pieSeries.slices.getIndex(0).set("active", true);
    });
    root._logo?.dispose();

});





