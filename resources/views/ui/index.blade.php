@extends('ui.layout')

@section('content')
@component('ui.partials.page-card', [
    'title' => 'Painel - Gestão de Avarias',
    'subtitle' => 'Escolha uma secção para continuar.',
    'actions' => ''
])
    <div id="metricsPanel" class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4"></div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <a href="/ui/tickets" class="rounded-2xl border border-white/10 bg-slate-950/60 p-5 transition hover:border-cyan-400/40 hover:bg-slate-800/70">
            <p class="text-lg font-semibold text-white">Tickets</p>
            <p class="mt-2 text-sm text-slate-400">Consultar, filtrar e ver detalhes das ocorrências.</p>
        </a>
        <a href="/ui/equipments" class="rounded-2xl border border-white/10 bg-slate-950/60 p-5 transition hover:border-cyan-400/40 hover:bg-slate-800/70">
            <p class="text-lg font-semibold text-white">Equipamentos</p>
            <p class="mt-2 text-sm text-slate-400">Visualizar ativos e respetivas salas.</p>
        </a>
        <a href="/ui/users" class="rounded-2xl border border-white/10 bg-slate-950/60 p-5 transition hover:border-cyan-400/40 hover:bg-slate-800/70">
            <p class="text-lg font-semibold text-white">Utilizadores</p>
            <p class="mt-2 text-sm text-slate-400">Acompanhar contas e perfis da equipa.</p>
        </a>
        <a href="/ui/audits" class="rounded-2xl border border-white/10 bg-slate-950/60 p-5 transition hover:border-cyan-400/40 hover:bg-slate-800/70">
            <p class="text-lg font-semibold text-white">Auditoria</p>
            <p class="mt-2 text-sm text-slate-400">Rever alterações recentes do sistema.</p>
        </a>
        <a href="/calendar" class="rounded-2xl border border-white/10 bg-slate-950/60 p-5 transition hover:border-cyan-400/40 hover:bg-slate-800/70">
            <p class="text-lg font-semibold text-white">Agenda</p>
            <p class="mt-2 text-sm text-slate-400">Abrir a vista de calendário para planeamento.</p>
        </a>
        <a href="/analytics/export/pdf" class="rounded-2xl border border-white/10 bg-slate-950/60 p-5 transition hover:border-cyan-400/40 hover:bg-slate-800/70">
            <p class="text-lg font-semibold text-white">Relatórios</p>
            <p class="mt-2 text-sm text-slate-400">Exportar PDF/CSV com o estado das intervenções.</p>
        </a>
    </div>
@endcomponent
@endsection

@push('scripts')
<script>
async function loadMetrics(){
    // Apenas técnicos e administradores podem ver analytics
    const userRole = '{{ $user->profile->name ?? "" }}';
    if (userRole !== 'technician' && userRole !== 'admin') {
        // Para usuários normais, esconder o painel de métricas ou mostrar mensagem
        const panel = document.getElementById('metricsPanel');
        if (panel) {
            panel.innerHTML = '<div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5 col-span-full"><p class="text-sm text-slate-400">Painel de métricas disponível apenas para técnicos e administradores</p></div>';
        }
        return;
    }
    
    const res = await fetch('/analytics', {headers: authHeader()});
    if(!res.ok) return;
    const data = await res.json();
    const panel = document.getElementById('metricsPanel');
    panel.innerHTML = [
        ['Tempo médio de resolução', `${data.average_resolution_minutes ?? 0} min`],
        ['Tempo médio de espera', `${data.average_waiting_minutes ?? 0} min`],
        ['Tickets em aberto', `${data.open_tickets ?? 0}`],
        ['Tickets fechados', `${data.closed_tickets ?? 0}`],
    ].map(([label, value]) => `<div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5"><p class="text-sm text-slate-400">${label}</p><p class="mt-2 text-2xl font-semibold text-white">${value}</p></div>`).join('');
}

window.addEventListener('load', loadMetrics);
</script>
@endpush
