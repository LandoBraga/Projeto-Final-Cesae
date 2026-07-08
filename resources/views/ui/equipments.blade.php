@extends('ui.layout')

@section('content')
@component('ui.partials.page-card', [
    'title' => 'Equipamentos',
    'subtitle' => 'Lista dos ativos registados no sistema.',
    'actions' => '<a href="/ui" class="rounded-full border border-cyan-400/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-300 transition hover:bg-cyan-500/20">← Voltar ao painel</a>'
])
    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-white/10 bg-slate-900/70 p-4 md:flex-row md:items-end">
        <div class="flex-1">
            <label for="equipmentSearch" class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Pesquisa</label>
            <input id="equipmentSearch" type="text" placeholder="Pesquisar por nome, sala ou ID" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-white placeholder-slate-500 outline-none focus:border-cyan-500">
        </div>
        <div class="w-full md:w-44">
            <label for="equipmentStatus" class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Estado</label>
            <select id="equipmentStatus" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
                <option value="">Todos</option>
                <option value="active">Ativos</option>
                <option value="inactive">Inativos</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-950/60">
        <table id="eqTable" class="min-w-full divide-y divide-white/10 text-sm text-slate-300">
            <thead class="bg-slate-900/80 text-left text-slate-200"><tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Nome</th><th class="px-4 py-3">Sala</th><th class="px-4 py-3">Ativo</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
@endcomponent
@endsection

@push('scripts')
<script>
let allEquipments = [];

function isEquipmentActive(equipment) {
    return equipment.active === true || equipment.active === 1 || equipment.active === '1' || String(equipment.active).toLowerCase() === 'true';
}

function renderEquipments() {
    const searchValue = document.getElementById('equipmentSearch').value.toLowerCase();
    const statusValue = document.getElementById('equipmentStatus').value;
    const tbody = document.querySelector('#eqTable tbody');
    tbody.innerHTML = '';

    const filtered = allEquipments.filter((equipment) => {
        const haystack = `${equipment.id} ${equipment.name || ''} ${equipment.room?.name || ''}`.toLowerCase();
        const matchesSearch = !searchValue || haystack.includes(searchValue);
        const matchesStatus = !statusValue || (statusValue === 'active' && isEquipmentActive(equipment)) || (statusValue === 'inactive' && !isEquipmentActive(equipment));
        return matchesSearch && matchesStatus;
    });

    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Nenhum equipamento encontrado.</td></tr>';
        return;
    }

    for (const equipment of filtered) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="px-4 py-3">${equipment.id}</td><td class="px-4 py-3">${equipment.name || ''}</td><td class="px-4 py-3">${equipment.room ? equipment.room.name : ''}</td><td class="px-4 py-3">${isEquipmentActive(equipment) ? 'Sim' : 'Não'}</td>`;
        tbody.appendChild(tr);
    }
}

async function loadEquipments(){
    const res = await fetch('/equipments', {headers: authHeader()});
    if(res.status===401){ alert('Autenticação necessária.'); window.location='/ui/login'; return; }
    if(res.status===403){ alert('Sem permissão para verificar equipamentos.'); return; }
    const data = await res.json();
    allEquipments = data.equipments || [];
    renderEquipments();
}

window.addEventListener('load', () => {
    document.getElementById('equipmentSearch').addEventListener('input', renderEquipments);
    document.getElementById('equipmentStatus').addEventListener('change', renderEquipments);
    loadEquipments();
});
</script>
@endpush
