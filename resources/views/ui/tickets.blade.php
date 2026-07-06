@extends('ui.layout')

@section('content')
@component('ui.partials.page-card', [
    'title' => 'Tickets',
    'subtitle' => 'Filtre e consulte as ocorrências registadas.',
    'actions' => '<a href="/ui" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/10">Voltar atrás</a>'
])
    <div class="mb-6 grid gap-3 rounded-2xl border border-white/10 bg-slate-950/60 p-4 lg:grid-cols-4 xl:grid-cols-6">
        <label class="text-sm text-slate-300">Equipamento ID
            <input id="filter_equipment" class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
        </label>
        <label class="text-sm text-slate-300">Sala ID
            <input id="filter_room" class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
        </label>
        <label class="text-sm text-slate-300">Técnico ID
            <input id="filter_technician" class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
        </label>
        <label class="text-sm text-slate-300">Prioridade
            <select id="filter_priority" class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
                <option value="">Todas</option>
                <option value="baixa">Baixa</option>
                <option value="média">Média</option>
                <option value="alta">Alta</option>
                <option value="crítica">Crítica</option>
            </select>
        </label>
        <label class="text-sm text-slate-300">Estado
            <input id="filter_status" class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
        </label>
        <button id="btnSearch" class="mt-6 rounded-xl bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400">Pesquisar</button>
    </div>
    <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-950/60">
        <table id="ticketsTable" class="min-w-full divide-y divide-white/10 text-sm text-slate-300">
            <thead class="bg-slate-900/80 text-left text-slate-200">
                <tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Título</th><th class="px-4 py-3">Prioridade</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Equipamento</th><th class="px-4 py-3">Sala</th><th class="px-4 py-3">Técnico</th><th class="px-4 py-3">Ações</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
@endcomponent
@endsection

@push('scripts')
<script>
async function loadTickets(){
    const params = new URLSearchParams();
    const eq = document.getElementById('filter_equipment').value;
    const rm = document.getElementById('filter_room').value;
    const tech = document.getElementById('filter_technician').value;
    const priority = document.getElementById('filter_priority').value;
    const status = document.getElementById('filter_status').value;
    if(eq) params.append('equipment_id', eq);
    if(rm) params.append('room_id', rm);
    if(tech) params.append('technician_id', tech);
    if(priority) params.append('priority', priority);
    if(status) params.append('status', status);

    const res = await fetch('/tickets?'+params.toString(), {headers: authHeader()});
    if(res.status===401){ alert('Autenticação necessária. Faça login.'); window.location='/ui/login'; return; }
    const data = await res.json();
    const tbody = document.querySelector('#ticketsTable tbody');
    tbody.innerHTML = '';
    for(const t of data.tickets){
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${t.id}</td><td>${t.title}</td><td>${t.priority}</td><td>${t.status}</td><td>${t.equipment? t.equipment.name : ''}</td><td>${t.room? t.room.name : ''}</td><td>${t.technician? t.technician.name : ''}</td><td><a href='/ui/tickets/${t.id}'>Ver</a></td>`;
        tbody.appendChild(tr);
    }
}

document.getElementById('btnSearch').addEventListener('click', loadTickets);
window.addEventListener('load', loadTickets);
</script>
@endpush
