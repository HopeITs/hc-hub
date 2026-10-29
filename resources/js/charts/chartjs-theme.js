import { brandColors, isDarkTheme, onThemeChange } from "./brand-colors.js";

/**
 * Chart.js in dark mode. Chart.js paints anything a chart leaves unset in its
 * own defaults (#666 text, black grid lines and tooltips), which vanish on a
 * dark page. In dark mode those defaults become the brand neutrals; in light
 * mode Chart.js keeps its stock defaults, so light charts look exactly as
 * before. A theme switch redraws every chart on the page.
 *
 * Ported from hc-hr's chartjs-brand.js, limited to dark mode (hc-hr also
 * re-colours light charts; that is a separate brand decision).
 */
function snapshot(Chart) {
    const tooltip = Chart.defaults.plugins.tooltip;

    return {
        color: Chart.defaults.color,
        borderColor: Chart.defaults.borderColor,
        tooltip: {
            backgroundColor: tooltip.backgroundColor,
            borderColor: tooltip.borderColor,
            titleColor: tooltip.titleColor,
            bodyColor: tooltip.bodyColor,
            footerColor: tooltip.footerColor,
        },
    };
}

function apply(Chart, light) {
    if (!isDarkTheme()) {
        Chart.defaults.color = light.color;
        Chart.defaults.borderColor = light.borderColor;
        Object.assign(Chart.defaults.plugins.tooltip, light.tooltip);

        return;
    }

    const colors = brandColors();

    Chart.defaults.color = colors.ink2;
    Chart.defaults.borderColor = colors.lineSoft;
    Object.assign(Chart.defaults.plugins.tooltip, {
        backgroundColor: colors.ink,
        borderColor: colors.ink,
        titleColor: colors.onInk,
        bodyColor: colors.onInk,
        footerColor: colors.onInk,
    });
}

export default function applyChartJsTheme(Chart) {
    const light = snapshot(Chart);

    apply(Chart, light);

    onThemeChange(() => {
        apply(Chart, light);
        Object.values(Chart.instances).forEach((chart) => chart.update("none"));
    });
}
