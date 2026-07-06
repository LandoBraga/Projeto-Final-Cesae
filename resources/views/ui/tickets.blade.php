@extends('ui.layout')

@section('content')
<h2>Tickets</h2>
<p><a href="/ui">Voltar atrás</a></p>
<div>
    <label>Equipamento ID: <input id="filter_equipment"></label>
    <label>Sala ID: <input id="filter_room"></label>
    <label>Técnico ID: <input id="filter_technician"></label>
    <label>Prioridade:
        <select id="filter_priority">
            <option value="">Todas</option>
            <option value="baixa">Baixa</option>
            <option value="média">Média</option>
            <option value="alta">Alta</option>
            <option value="crítica">Crítica</option>
        </select>
    </label>
    <label>Estado: <input id="filter_status"></label>
    <button id="btnSearch">Pesquisar</button>
</div>
<table id="ticketsTable">
    <thead><tr><th>ID</th><th>Título</th><th>Prioridade</th><th>Estado</th><th>Equipamento</th><th>Sala</th><th>Técnico</th><th>Ações</th></tr></thead>
    <tbody></tbody>
</table>
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
