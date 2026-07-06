@extends('ui.layout')

@section('content')
@component('ui.partials.page-card', [
    'title' => 'Auditoria',
    'subtitle' => 'Últimos 200 registos de atividade do sistema.',
    'actions' => '<a href="/ui" class="rounded-full border border-cyan-400/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-300 transition hover:bg-cyan-500/20">← Voltar ao painel</a>'
])
    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-white/10 bg-slate-900/70 p-4 md:flex-row md:items-end">
        <div class="flex-1">
            <label for="auditsSearch" class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Pesquisa</label>
            <input id="auditsSearch" type="text" placeholder="Pesquisar por utilizador, entidade ou evento" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-white placeholder-slate-500 outline-none focus:border-cyan-500">
        </div>
        <div class="w-full md:w-56">
            <label for="auditsEvent" class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Evento</label>
            <select id="auditsEvent" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
                <option value="">Todos</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/60">
        <table id="auditsTable" class="min-w-full divide-y divide-white/10 text-sm text-slate-300">
            <thead class="bg-slate-900/80 text-left text-slate-200"><tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Utilizador</th><th class="px-4 py-3">Entidade</th><th class="px-4 py-3">ID da entidade</th><th class="px-4 py-3">Evento</th><th class="px-4 py-3">Antigo</th><th class="px-4 py-3">Novo</th><th class="px-4 py-3">Quando</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
@endcomponent
@endsection

@push('scripts')
<script>
let allAudits = [];

function renderAudits() {
    const searchValue = document.getElementById('auditsSearch').value.toLowerCase();
    const eventValue = document.getElementById('auditsEvent').value;
    const tbody = document.querySelector('#auditsTable tbody');
    tbody.innerHTML = '';

    const filtered = allAudits.filter((audit) => {
        const haystack = `${audit.user?.name || ''} ${audit.auditable_type || ''} ${audit.event || ''} ${audit.auditable_id || ''}`.toLowerCase();
        const matchesSearch = !searchValue || haystack.includes(searchValue);
        const matchesEvent = !eventValue || (audit.event || '').toLowerCase() === eventValue.toLowerCase();
        return matchesSearch && matchesEvent;
    });

    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">Nenhum registo encontrado.</td></tr>';
        return;
    }

    for (const audit of filtered) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="px-4 py-3">${audit.id}</td><td class="px-4 py-3">${audit.user ? audit.user.name : ''}</td><td class="px-4 py-3">${audit.auditable_type || ''}</td><td class="px-4 py-3">${audit.auditable_id || ''}</td><td class="px-4 py-3">${audit.event || ''}</td><td class="px-4 py-3"><pre class="whitespace-pre-wrap text-xs">${JSON.stringify(audit.old_values || {})}</pre></td><td class="px-4 py-3"><pre class="whitespace-pre-wrap text-xs">${JSON.stringify(audit.new_values || {})}</pre></td><td class="px-4 py-3">${audit.created_at || ''}</td>`;
        tbody.appendChild(tr);
    }
}

async function loadAudits(){
    const res = await fetch('/admin/audits', {headers: authHeader()});
    if(res.status===401){ alert('Autenticação necessária.'); window.location='/ui/login'; return; }
    const data = await res.json();
    allAudits = data.audits || [];

    const eventSelect = document.getElementById('auditsEvent');
    const events = [...new Set(allAudits.map((audit) => audit.event).filter(Boolean))].sort();
    eventSelect.innerHTML = '<option value="">Todos</option>' + events.map((event) => `<option value="${event}">${event}</option>`).join('');

    renderAudits();
}

window.addEventListener('load', () => {
    document.getElementById('auditsSearch').addEventListener('input', renderAudits);
    document.getElementById('auditsEvent').addEventListener('change', renderAudits);
    loadAudits();
});
</script>
@endpush
