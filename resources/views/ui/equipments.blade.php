@extends('ui.layout')

@section('content')
<h2>Equipamentos</h2>
<table id="eqTable"><thead><tr><th>ID</th><th>Nome</th><th>Sala</th><th>Ativo</th></tr></thead><tbody></tbody></table>
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
