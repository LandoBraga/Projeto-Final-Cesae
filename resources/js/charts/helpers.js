/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
|
| Funções auxiliares utilizadas por toda a biblioteca Chart.js.
|
*/

/*
|--------------------------------------------------------------------------
| CSS Variables
|--------------------------------------------------------------------------
*/

export function getCssVariable(name) {

    return getComputedStyle(document.documentElement)
        .getPropertyValue(name)
        .trim();

}

/*
|--------------------------------------------------------------------------
| Theme
|--------------------------------------------------------------------------
*/

export function isDarkMode() {

    return document.documentElement.classList.contains("dark");

}

/*
|--------------------------------------------------------------------------
| Colors
|--------------------------------------------------------------------------
*/

export function getTextColor() {

    return getCssVariable("--text") || "#1F2937";

}

export function getSoftTextColor() {

    return getCssVariable("--text-soft") || "#6B7280";

}

export function getBorderColor() {

    return getCssVariable("--border") || "#E5E7EB";

}

export function getSurfaceColor() {

    return getCssVariable("--surface") || "#FFFFFF";

}

export function getSurfaceAltColor() {

    return getCssVariable("--surface-2") || "#F8FAFC";

}

export function getGridColor() {

    return isDarkMode()

        ? "rgba(255,255,255,0.08)"

        : "rgba(15,23,42,0.08)";

}

export function getTooltipBackground() {

    return isDarkMode()

        ? "#111827"

        : "#FFFFFF";

}

export function getTooltipText() {

    return isDarkMode()

        ? "#F9FAFB"

        : "#111827";

}

/*
|--------------------------------------------------------------------------
| Numbers
|--------------------------------------------------------------------------
*/

export function formatNumber(value) {

    return new Intl.NumberFormat("pt-PT").format(value);

}

export function formatCurrency(value) {

    return new Intl.NumberFormat("pt-PT", {

        style: "currency",

        currency: "EUR"

    }).format(value);

}

export function formatPercent(value) {

    return `${Number(value).toFixed(1)}%`;

}

/*
|--------------------------------------------------------------------------
| Arrays
|--------------------------------------------------------------------------
*/

export function sum(values = []) {

    return values.reduce(

        (total, value) => total + Number(value),

        0

    );

}

export function max(values = []) {

    return Math.max(...values);

}

export function min(values = []) {

    return Math.min(...values);

}
