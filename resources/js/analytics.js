/*
|--------------------------------------------------------------------------
| Analytics Dashboard
|--------------------------------------------------------------------------
|
| Dashboard analítico da aplicação.
|
*/

import {

    applyChartTheme,

    createBarChart,

    createLineChart,

    createDoughnutChart,

    PALETTE

} from "./charts";

document.addEventListener("DOMContentLoaded", () => {

    /*
    |--------------------------------------------------------------------------
    | Verificar Página
    |--------------------------------------------------------------------------
    */

    const dashboard = document.getElementById("kpiPanel");

    if (!dashboard) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Aplicar Tema Global
    |--------------------------------------------------------------------------
    */

    applyChartTheme();

    /*
    |--------------------------------------------------------------------------
    | Elementos da Página
    |--------------------------------------------------------------------------
    */

    const elements = {

        kpiPanel: document.getElementById("kpiPanel"),

        analyticsMessage: document.getElementById("analyticsMessage"),

        statusChart: document.getElementById("statusChart"),

        trendChart: document.getElementById("trendChart"),

        costChart: document.getElementById("costChart"),

        equipmentChart: document.getElementById("equipmentChart"),

        equipmentLegend: document.getElementById("equipmentLegend"),

        equipmentTotal: document.getElementById("equipmentTotal"),

        activityTimeline: document.getElementById("activityTimeline"),

        topEquipments: document.getElementById("topEquipments"),

        topRooms: document.getElementById("topRooms"),

        topTechnicians: document.getElementById("topTechnicians")

    };

    /*
    |--------------------------------------------------------------------------
    | Instâncias dos Charts
    |--------------------------------------------------------------------------
    */

    const charts = {

        status: null,

        trend: null,

        cost: null,

        equipment: null

    };

    /*
    |--------------------------------------------------------------------------
    | Refresh
    |--------------------------------------------------------------------------
    */

    let refreshTimer = null;

        /*
    |--------------------------------------------------------------------------
    | Mensagens
    |--------------------------------------------------------------------------
    */

    function showMessage(message, type = "success") {

        if (!elements.analyticsMessage) {
            return;
        }

        const box = elements.analyticsMessage;

        box.className =
            "mt-6 rounded-2xl border px-5 py-4 text-sm font-medium";

        if (type === "error") {

            box.classList.add(

                "border-red-300",

                "bg-red-50",

                "text-red-700",

                "dark:border-red-900",

                "dark:bg-red-950/20",

                "dark:text-red-400"

            );

        } else {

            box.classList.add(

                "border-emerald-300",

                "bg-emerald-50",

                "text-emerald-700",

                "dark:border-emerald-900",

                "dark:bg-emerald-950/20",

                "dark:text-emerald-400"

            );

        }

        box.textContent = message;

    }

    function clearMessage() {

        if (!elements.analyticsMessage) {
            return;
        }

        elements.analyticsMessage.className = "hidden";

        elements.analyticsMessage.textContent = "";

    }

    /*
    |--------------------------------------------------------------------------
    | Headers
    |--------------------------------------------------------------------------
    */

    function getAuthHeaders() {

        const headers = {

            Accept: "application/json",

            "X-Requested-With": "XMLHttpRequest"

        };

        if (typeof window.authHeader === "function") {

            return {

                ...headers,

                ...window.authHeader()

            };

        }

        const token = localStorage.getItem("api_token");

        if (token) {

            headers.Authorization = `Bearer ${token}`;

        }

        return headers;

    }

    /*
    |--------------------------------------------------------------------------
    | Destroy Helpers
    |--------------------------------------------------------------------------
    */

    function destroyChart(name) {

        if (charts[name]) {

            charts[name].destroy();

            charts[name] = null;

        }

    }

    function destroyAllCharts() {

        Object.keys(charts).forEach(destroyChart);

    }

        /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */

    async function fetchAnalytics() {

        clearMessage();

        try {

            const response = await fetch("/analytics", {

                method: "GET",

                headers: getAuthHeaders(),

                credentials: "include"

            });

            if (!response.ok) {

                throw new Error(`Erro ${response.status}`);

            }

            return await response.json();

        }

        catch (error) {

            console.error(error);

            showMessage(

                "Não foi possível carregar os dados analíticos.",

                "error"

            );

            return null;

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Refresh
    |--------------------------------------------------------------------------
    */

    async function refreshDashboard() {

        const analytics = await fetchAnalytics();

        if (!analytics) {

            return;

        }

        renderKPIs(analytics);

        renderStatusChart(analytics);

        renderTrendChart(analytics);

        renderCostChart(analytics);

        renderEquipmentChart(analytics);

        renderOperationalMetrics(analytics);

        renderActivity(analytics);

        renderSummary(analytics);

    }

    /*
    |--------------------------------------------------------------------------
    | Auto Refresh
    |--------------------------------------------------------------------------
    */

    function startAutoRefresh() {

        stopAutoRefresh();

        refreshTimer = setInterval(() => {

            refreshDashboard();

        }, 60000);

    }

    function stopAutoRefresh() {

        if (refreshTimer) {

            clearInterval(refreshTimer);

            refreshTimer = null;

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Atualizar quando regressa ao separador
    |--------------------------------------------------------------------------
    */

    document.addEventListener(

        "visibilitychange",

        () => {

            if (document.hidden) {

                stopAutoRefresh();

            }

            else {

                refreshDashboard();

                startAutoRefresh();

            }

        }

    );

        /*
    |--------------------------------------------------------------------------
    | KPI Cards
    |--------------------------------------------------------------------------
    */

    function renderKPIs(data) {

        if (!elements.kpiPanel) {
            return;
        }

        const cards = [

            {
                title: "Tempo Médio de Resolução",
                value: `${Math.round(data.average_resolution_minutes ?? 0)} min`,
                subtitle: "MTTR",
                icon: "🛠️",
                color: "emerald"
            },

            {
                title: "Tempo Médio de Espera",
                value: `${Math.round(data.average_waiting_minutes ?? 0)} min`,
                subtitle: "Tempo até atribuição",
                icon: "⏱️",
                color: "blue"
            },

            {
                title: "Tickets Abertos",
                value: data.open_tickets ?? 0,
                subtitle: "Ocorrências ativas",
                icon: "📂",
                color: "amber"
            },

            {
                title: "Tickets Resolvidos",
                value: data.closed_tickets ?? 0,
                subtitle: "Intervenções concluídas",
                icon: "✅",
                color: "purple"
            }

        ];

        const colors = {

            emerald: {
                icon: "bg-emerald-500/10 text-emerald-500",
                badge: "bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
            },

            blue: {
                icon: "bg-blue-500/10 text-blue-500",
                badge: "bg-blue-500/10 text-blue-600 dark:text-blue-400"
            },

            amber: {
                icon: "bg-amber-500/10 text-amber-500",
                badge: "bg-amber-500/10 text-amber-600 dark:text-amber-400"
            },

            purple: {
                icon: "bg-purple-500/10 text-purple-500",
                badge: "bg-purple-500/10 text-purple-600 dark:text-purple-400"
            }

        };

        elements.kpiPanel.innerHTML = cards.map(card => {

            const style = colors[card.color];

            return `

                <article
                    class="group overflow-hidden rounded-3xl border border-[var(--border)] bg-[var(--surface)] transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">

                    <div class="p-6">

                        <div class="flex items-start justify-between">

                            <div>

                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-soft">

                                    ${card.title}

                                </p>

                                <h3 class="mt-5 text-4xl font-black tracking-tight">

                                    ${card.value}

                                </h3>

                            </div>

                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl ${style.icon}">

                                <span class="text-2xl">

                                    ${card.icon}

                                </span>

                            </div>

                        </div>

                        <div class="mt-8 flex items-center justify-between">

                            <span class="rounded-full px-3 py-1 text-xs font-semibold ${style.badge}">

                                ${card.subtitle}

                            </span>

                        </div>

                    </div>

                </article>

            `;

        }).join("");

    }

        /*
    |--------------------------------------------------------------------------
    | Estado dos Tickets
    |--------------------------------------------------------------------------
    */

    function renderStatusChart(data) {

        if (!elements.statusChart) {
            return;
        }

        destroyChart("status");

        const breakdown = data.ticket_status_breakdown ?? {

            labels: [],

            data: []

        };

        charts.status = createBarChart({

            canvas: elements.statusChart,

            labels: breakdown.labels,

            data: breakdown.data,

            label: "Tickets",

            options: {

                plugins: {

                    tooltip: {

                        callbacks: {

                            label(context) {

                                return `${context.raw} tickets`;

                            }

                        }

                    }

                }

            }

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Evolução Mensal
    |--------------------------------------------------------------------------
    */

    function renderTrendChart(data) {

        if (!elements.trendChart) {
            return;
        }

        destroyChart("trend");

        const trend = data.monthly_tickets ?? {

            labels: [],

            open: [],

            in_progress: [],

            closed: []

        };

        charts.trend = createLineChart({

            canvas: elements.trendChart,

            labels: trend.labels,

            datasets: [

                {

                    label: "Abertos",

                    data: trend.open,

                    color: "#3B82F6",

                    fill: false

                },

                {

                    label: "Em Curso",

                    data: trend.in_progress,

                    color: "#F59E0B",

                    fill: false

                },

                {

                    label: "Fechados",

                    data: trend.closed,

                    color: "#22C55E",

                    fill: false

                }

            ],

            options: {

                plugins: {

                    legend: {

                        display: true,

                        position: "bottom"

                    }

                }

            }

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Custos Mensais
    |--------------------------------------------------------------------------
    */

    function renderCostChart(data) {

        if (!elements.costChart) {
            return;
        }

        destroyChart("cost");

        const costs = data.monthly_cost ?? {

            labels: [],

            data: []

        };

        charts.cost = createBarChart({

            canvas: elements.costChart,

            labels: costs.labels,

            data: costs.data,

            label: "Custos (€)",

            options: {

                plugins: {

                    tooltip: {

                        callbacks: {

                            label(context) {

                                return `${Number(context.raw).toFixed(2)} €`;

                            }

                        }

                    }

                }

            }

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Equipamentos
    |--------------------------------------------------------------------------
    */

    function renderEquipmentChart(data) {

        if (!elements.equipmentChart) {
            return;
        }

        destroyChart("equipment");

        const equipment = data.top_equipments ?? [];

        const labels = equipment.map(item => item.name);

        const values = equipment.map(item => Number(item.total));

        charts.equipment = createDoughnutChart({

            canvas: elements.equipmentChart,

            labels,

            data: values,

            options: {

                cutout: "72%",

                plugins: {

                    tooltip: {

                        callbacks: {

                            label(context) {

                                return `${context.label}: ${context.raw}`;

                            }

                        }

                    }

                }

            }

        });

        const total = values.reduce(

            (sum, value) => sum + value,

            0

        );

        if (elements.equipmentTotal) {

            elements.equipmentTotal.textContent = total;

        }

        if (!elements.equipmentLegend) {

            return;

        }

        if (!labels.length) {

            elements.equipmentLegend.innerHTML = `

                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-6 text-center text-soft">

                    Sem dados disponíveis.

                </div>

            `;

            return;

        }

        elements.equipmentLegend.innerHTML = labels.map((label, index) => {

            const value = values[index];

            const percent = total > 0

                ? ((value / total) * 100).toFixed(1)

                : 0;

            return `

                <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface)] px-5 py-4">

                    <div class="flex items-center gap-4">

                        <span

                            class="h-3.5 w-3.5 rounded-full"

                            style="background:${PALETTE[index % PALETTE.length]}">

                        </span>

                        <div>

                            <div class="font-medium">

                                ${label}

                            </div>

                            <div class="text-xs text-soft">

                                ${percent}%

                            </div>

                        </div>

                    </div>

                    <div class="text-lg font-bold">

                        ${value}

                    </div>

                </div>

            `;

        }).join("");

    }

        /*
    |--------------------------------------------------------------------------
    | Rankings
    |--------------------------------------------------------------------------
    */

    function renderRanking(container, items = []) {

        if (!container) {
            return;
        }

        if (!items.length) {

            container.innerHTML = `

                <div class="flex items-center justify-center h-32 text-soft">

                    Sem informação disponível.

                </div>

            `;

            return;

        }

        container.innerHTML = items.map((item, index) => `

            <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface)] px-5 py-4">

                <div class="flex items-center gap-4">

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/10 text-blue-500 font-bold">

                        ${index + 1}

                    </div>

                    <div>

                        <div class="font-semibold">

                            ${item.name}

                        </div>

                        <div class="text-sm text-soft">

                            ${item.subtitle ?? ""}

                        </div>

                    </div>

                </div>

                <span class="rounded-full bg-blue-500/10 px-3 py-1 text-sm font-semibold text-blue-500">

                    ${item.total}

                </span>

            </div>

        `).join("");

    }

    /*
    |--------------------------------------------------------------------------
    | Timeline
    |--------------------------------------------------------------------------
    */

    function renderActivity(data) {

        if (!elements.activityTimeline) {
            return;
        }

        const activities = data.recent_activity ?? [];

        if (!activities.length) {

            elements.activityTimeline.innerHTML = `

                <div class="flex items-center justify-center h-40 text-soft">

                    Nenhuma atividade recente.

                </div>

            `;

            return;

        }

        elements.activityTimeline.innerHTML = activities.map(item => `

            <div class="flex gap-5 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-5">

                <div class="mt-1 flex h-11 w-11 items-center justify-center rounded-xl bg-blue-500/10">

                    <div class="h-3 w-3 rounded-full bg-blue-500"></div>

                </div>

                <div class="flex-1">

                    <div class="flex items-center justify-between gap-4">

                        <h4 class="font-semibold">

                            ${item.title}

                        </h4>

                        <span class="text-xs text-soft">

                            ${item.time}

                        </span>

                    </div>

                    <p class="mt-2 text-sm text-soft">

                        ${item.description}

                    </p>

                </div>

            </div>

        `).join("");

    }

    /*
    |--------------------------------------------------------------------------
    | Métricas Operacionais
    |--------------------------------------------------------------------------
    */

    function renderOperationalMetrics(data) {

        const metrics = {

            metricMttr:
                `${Math.round(data.average_resolution_minutes ?? 0)} min`,

            metricWaiting:
                `${Math.round(data.average_waiting_minutes ?? 0)} min`,

            metricAvailability:
                `${data.system_availability ?? 99.9}%`,

            metricSla:
                `${data.sla_success ?? 0}%`

        };

        Object.entries(metrics).forEach(([id, value]) => {

            const element = document.getElementById(id);

            if (element) {

                element.textContent = value;

            }

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Resumo
    |--------------------------------------------------------------------------
    */

    function renderSummary(data) {

        renderRanking(

            elements.topEquipments,

            data.top_equipments ?? []

        );

        renderRanking(

            elements.topRooms,

            data.top_rooms ?? []

        );

        renderRanking(

            elements.topTechnicians,

            data.top_technicians ?? []

        );

    }

        /*
    |--------------------------------------------------------------------------
    | Inicialização
    |--------------------------------------------------------------------------
    */

    async function initDashboard() {

        const analytics = await fetchAnalytics();

        if (!analytics) {
            return;
        }

        renderKPIs(analytics);

        renderStatusChart(analytics);

        renderTrendChart(analytics);

        renderCostChart(analytics);

        renderEquipmentChart(analytics);

        renderOperationalMetrics(analytics);

        renderActivity(analytics);

        renderSummary(analytics);

        startAutoRefresh();

    }

    /*
    |--------------------------------------------------------------------------
    | Atualização Manual
    |--------------------------------------------------------------------------
    */

    window.refreshAnalyticsDashboard = async function () {

        await refreshDashboard();

    };

    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    */

    window.addEventListener("beforeunload", () => {

        stopAutoRefresh();

        destroyAllCharts();

    });

    /*
    |--------------------------------------------------------------------------
    | Arranque
    |--------------------------------------------------------------------------
    */

    initDashboard();

});
