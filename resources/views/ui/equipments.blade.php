@extends('ui.layout')

@section('content')
@component('ui.partials.page-card', [
    'title' => 'Equipamentos',
    'subtitle' => 'Lista dos ativos registados no sistema.',
    'actions' => '<a href="/ui" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/10">Voltar atrás</a>'
])
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
async function loadEquipments(){
    const res = await fetch('/admin/equipment', {headers: authHeader()});
    if(res.status===401){ alert('Autenticação necessária.'); window.location='/ui/login'; return; }
    const data = await res.json();
    const tbody = document.querySelector('#eqTable tbody'); tbody.innerHTML='';
    for(const e of data.equipments){
        const tr=document.createElement('tr');
        tr.innerHTML = `<td>${e.id}</td><td>${e.name}</td><td>${e.room? e.room.name : ''}</td><td>${e.active}</td>`;
        tbody.appendChild(tr);
    }
}
window.addEventListener('load', loadEquipments);
</script>
@endpush
