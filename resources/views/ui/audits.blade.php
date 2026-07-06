@extends('ui.layout')

@section('content')
<h2>Auditoria (últimos 200)</h2>
<table id="auditsTable"><thead><tr><th>ID</th><th>Usuário</th><th>Entidade</th><th>Entidade ID</th><th>Evento</th><th>Antigo</th><th>Novo</th><th>Quando</th></tr></thead><tbody></tbody></table>
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
