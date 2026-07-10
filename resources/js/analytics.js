import { applyChartTheme, createBarChart, createDoughnutChart, createLineChart } from './charts';

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('kpiPanel')) {
        return;
    }

    applyChartTheme();

    const kpiPanel = document.getElementById('kpiPanel');
    const statusChartCanvas = document.getElementById('statusChart');
    const trendChartCanvas = document.getElementById('trendChart');
    const costChartCanvas = document.getElementById('costChart');
    const equipmentChartCanvas = document.getElementById('equipmentChart');
    const equipmentLegend = document.getElementById('equipmentLegend');
    const equipmentTotal = document.getElementById('equipmentTotal');
    const activityTimeline = document.getElementById('activityTimeline');
    const topEquipments = document.getElementById('topEquipments');
    const topRooms = document.getElementById('topRooms');
    const topTechnicians = document.getElementById('topTechnicians');
    const analyticsMessage = document.getElementById('analyticsMessage');

    let charts = {
        status: null,
        trend: null,
        cost: null,
        equipment: null,
    };

    let refreshTimer = null;

    function showMessage(message, type = 'success') {
        if (!analyticsMessage) return;
        analyticsMessage.className = 'mt-6 rounded-2xl border px-5 py-4 text-sm font-medium';
        analyticsMessage.classList.remove('hidden');

        if (type === 'error') {
            analyticsMessage.classList.add('border-red-300', 'bg-red-50', 'text-red-700');
        } else {
            analyticsMessage.classList.add('border-emerald-300', 'bg-emerald-50', 'text-emerald-700');
        }

        analyticsMessage.textContent = message;
    }

    function clearMessage() {
        if (!analyticsMessage) return;
        analyticsMessage.classList.add('hidden');
        analyticsMessage.textContent = '';
    }

    function getAuthHeaders() {
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        if (typeof window.authHeader === 'function') {
            return { ...headers, ...window.authHeader() };
        }

        const token = localStorage.getItem('api_token');
        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }

        return headers;
    }

    async function fetchAnalytics() {
        clearMessage();

        try {
            const response = await fetch('/analytics', {
                method: 'GET',
                headers: getAuthHeaders(),
            });

            if (!response.ok) {
                throw new Error(`Erro ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Erro ao carregar Analytics:', error);
            showMessage('Não foi possível carregar os dados analíticos.', 'error');
            return null;
        }
    }

    function renderKPIs(data) {
        if (!kpiPanel) return;

        const cards = [
            {
                title: 'Tempo Médio de Resolução',
                value: `${Math.round(data.average_resolution_minutes ?? 0)} min`,
                subtitle: 'MTTR',
                accentClass: 'bg-emerald-500/10 text-emerald-500',
                pillClass: 'bg-emerald-500/10 text-emerald-500',
                icon: '🛠️',
            },
            {
                title: 'Tempo Médio de Espera',
                value: `${Math.round(data.average_waiting_minutes ?? 0)} min`,
                subtitle: 'Tempo até atribuição',
                accentClass: 'bg-blue-500/10 text-blue-500',
                pillClass: 'bg-blue-500/10 text-blue-500',
                icon: '⏱️',
            },
            {
                title: 'Tickets Abertos',
                value: data.open_tickets ?? 0,
                subtitle: 'Ocorrências ativas',
                accentClass: 'bg-amber-500/10 text-amber-500',
                pillClass: 'bg-amber-500/10 text-amber-500',
                icon: '📂',
            },
            {
                title: 'Tickets Resolvidos',
                value: data.closed_tickets ?? 0,
                subtitle: 'Intervenções concluídas',
                accentClass: 'bg-purple-500/10 text-purple-500',
                pillClass: 'bg-purple-500/10 text-purple-500',
                icon: '✅',
            },
        ];

        kpiPanel.innerHTML = cards.map((card) => `
            <article class="overflow-hidden rounded-3xl border border-[var(--border)] bg-[var(--surface)] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-soft">${card.title}</p>
                            <h3 class="mt-5 text-4xl font-black tracking-tight">${card.value}</h3>
                        </div>
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl ${card.accentClass}">
                            <span class="text-2xl">${card.icon}</span>
                        </div>
                    </div>
                    <div class="mt-8 flex items-center justify-between">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold ${card.pillClass}">${card.subtitle}</span>
                    </div>
                </div>
            </article>
        `).join('');
    }

    function renderStatusChart(data) {
        if (!statusChartCanvas) return;
        charts.status?.destroy();

        const breakdown = data.ticket_status_breakdown ?? { labels: [], data: [] };

        charts.status = createBarChart({
            canvas: statusChartCanvas,
            labels: breakdown.labels,
            data: breakdown.data,
            label: 'Tickets',
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.raw} tickets`,
                        },
                    },
                },
            },
        });
    }

    function renderTrendChart(data) {
        if (!trendChartCanvas) return;
        charts.trend?.destroy();

        const trend = data.monthly_tickets ?? { labels: [], open: [], in_progress: [], closed: [] };

        charts.trend = createLineChart(trendChartCanvas.getContext('2d'), {
            labels: trend.labels,
            datasets: [
                { label: 'Abertos', data: trend.open, color: '#3B82F6', fill: false },
                { label: 'Em Curso', data: trend.in_progress, color: '#F59E0B', fill: false },
                { label: 'Fechados', data: trend.closed, color: '#22C55E', fill: false },
            ],
            options: {
                plugins: {
                    legend: { display: true },
                },
            },
        });
    }

    function renderCostChart(data) {
        if (!costChartCanvas) return;
        charts.cost?.destroy();

        const monthlyCost = data.monthly_cost ?? { labels: [], data: [] };

        charts.cost = createBarChart({
            canvas: costChartCanvas,
            labels: monthlyCost.labels,
            data: monthlyCost.data,
            label: 'Custo (€)',
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.raw.toFixed(2)} €`,
                        },
                    },
                },
            },
        });
    }

    function renderEquipmentChart(data) {
        if (!equipmentChartCanvas) return;
        charts.equipment?.destroy();

        const equipment = data.top_equipments ?? [];
        const labels = equipment.map((item) => item.name);
        const values = equipment.map((item) => item.total);

        charts.equipment = createDoughnutChart({
            canvas: equipmentChartCanvas,
            labels,
            data: values,
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.label}: ${context.raw}`,
                        },
                    },
                },
            },
        });

        if (equipmentTotal) {
            equipmentTotal.textContent = values.reduce((sum, value) => sum + value, 0);
        }

        if (equipmentLegend) {
            equipmentLegend.innerHTML = labels.length
                ? labels.map((label, index) => `
                    <div class="flex items-center justify-between rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full" style="background:${['#22C55E', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6'][index % 5]}"></span>
                            <span class="text-sm font-medium">${label}</span>
                        </div>
                        <span class="text-sm font-bold">${values[index] ?? 0}</span>
                    </div>
                `).join('')
                : '<div class="p-4 text-sm text-soft">Sem dados suficientes.</div>';
        }
    }

    function renderRanking(container, list) {
        if (!container) return;

        if (!list || !list.length) {
            container.innerHTML = '<div class="p-6 text-center text-soft">Sem informação.</div>';
            return;
        }

        container.innerHTML = list.map((item, index) => `
            <div class="flex items-center justify-between p-5">
                <div class="flex items-center gap-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--surface-2)] font-bold">${index + 1}</div>
                    <div>
                        <div class="font-semibold">${item.name}</div>
                        <div class="mt-1 text-sm text-soft">${item.subtitle ?? ''}</div>
                    </div>
                </div>
                <span class="rounded-full bg-blue-500/10 px-3 py-1 text-sm font-semibold text-blue-500">${item.total}</span>
            </div>
        `).join('');
    }

    function renderActivity(data) {
        if (!activityTimeline) return;
        const activities = data.recent_activity ?? [];

        if (!activities.length) {
            activityTimeline.innerHTML = '<div class="p-10 text-center text-soft">Nenhuma atividade recente.</div>';
            return;
        }

        activityTimeline.innerHTML = activities.map((item) => `
            <div class="flex items-start gap-5 p-6">
                <div class="mt-1 flex h-11 w-11 items-center justify-center rounded-xl bg-blue-500/10">
                    <div class="h-3 w-3 rounded-full bg-blue-500"></div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between gap-4">
                        <h4 class="font-semibold">${item.title}</h4>
                        <span class="text-xs text-soft">${item.time}</span>
                    </div>
                    <p class="mt-2 text-sm text-soft">${item.description}</p>
                </div>
            </div>
        `).join('');
    }

    function renderSummary(data) {
        renderRanking(topEquipments, data.top_equipments);
        renderRanking(topRooms, data.top_rooms);
        renderRanking(topTechnicians, data.top_technicians);
    }

    async function refreshDashboard() {
        const analytics = await fetchAnalytics();
        if (!analytics) return;

        renderKPIs(analytics);
        renderStatusChart(analytics);
        renderTrendChart(analytics);
        renderCostChart(analytics);
        renderEquipmentChart(analytics);
        renderOperationalMetrics(analytics);
        renderActivity(analytics);
        renderSummary(analytics);
    }

    function renderOperationalMetrics(data) {
        const setValue = (id, value) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        };

        setValue('metricMttr', `${Math.round(data.average_resolution_minutes ?? 0)} min`);
        setValue('metricWaiting', `${Math.round(data.average_waiting_minutes ?? 0)} min`);
        setValue('metricAvailability', `${data.system_availability ?? 99.9}%`);
        setValue('metricSla', `${data.sla_success ?? 0}%`);
    }

    function startAutoRefresh() {
        clearInterval(refreshTimer);
        refreshTimer = setInterval(() => {
            refreshDashboard();
        }, 60000);
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshDashboard();
        }
    });

    async function initDashboard() {
        const analytics = await fetchAnalytics();
        if (!analytics) return;

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

    initDashboard();
});


