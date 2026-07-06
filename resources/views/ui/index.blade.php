@extends('ui.layout')

@section('content')
@component('ui.partials.page-card', [
    'title' => 'Painel - Gestão de Avarias',
    'subtitle' => 'Escolha uma secção para continuar.',
    'actions' => '<a href="/" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/10">Voltar para a página principal</a>'
])
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
    </div>
@endcomponent
@endsection
