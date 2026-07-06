@extends('ui.layout')

@section('content')
@component('ui.partials.page-card', [
    'title' => 'Auditoria',
    'subtitle' => 'Últimos 200 registos de atividade do sistema.',
    'actions' => '<a href="/ui" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/10">Voltar atrás</a>'
])
    <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/60">
        <table id="auditsTable" class="min-w-full divide-y divide-white/10 text-sm text-slate-300">
            <thead class="bg-slate-900/80 text-left text-slate-200"><tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Usuário</th><th class="px-4 py-3">Entidade</th><th class="px-4 py-3">Entidade ID</th><th class="px-4 py-3">Evento</th><th class="px-4 py-3">Antigo</th><th class="px-4 py-3">Novo</th><th class="px-4 py-3">Quando</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
@endcomponent
@endsection

@push('scripts')
<script>
async function loadAudits(){
    const res = await fetch('/admin/audits', {headers: authHeader()});
    if(res.status===401){ alert('Autenticação necessária.'); window.location='/ui/login'; return; }
    const data = await res.json();
    const tbody = document.querySelector('#auditsTable tbody'); tbody.innerHTML='';
    for(const a of data.audits){
        const tr=document.createElement('tr');
        tr.innerHTML = `<td>${a.id}</td><td>${a.user? a.user.name : ''}</td><td>${a.auditable_type}</td><td>${a.auditable_id}</td><td>${a.event}</td><td><pre>${JSON.stringify(a.old_values)}</pre></td><td><pre>${JSON.stringify(a.new_values)}</pre></td><td>${a.created_at}</td>`;
        tbody.appendChild(tr);
    }
}
window.addEventListener('load', loadAudits);
</script>
@endpush
