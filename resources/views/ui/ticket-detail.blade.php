@extends('ui.layout')

@section('content')
<h2>Detalhes do Ticket</h2>
<p><a href="/ui/tickets">Voltar atrás</a></p>
<div id="ticketDetails">
    <p>Carregando...</p>
</div>

<h3>Comentários internos</h3>
<div id="commentsSection">
    <p>Carregando comentários...</p>
</div>

<h3>Adicionar comentário</h3>
<form id="commentForm">
    <textarea id="commentText" rows="4" cols="60" placeholder="Escreva um comentário para outros técnicos..."></textarea><br>
    <button type="submit">Enviar comentário</button>
</form>

<h3>Ações</h3>
<div>
    <label>Técnico ID para atribuição manual: <input id="assignTechnicianId" type="number" min="1"></label>
    <button id="btnAssignManual">Atribuir Técnico</button>
    <button id="btnAssignAuto">Atribuir Técnico Automático</button>
</div>
<div>
    <button id="btnReopen">Reabrir Ticket</button>
</div>

<div id="ticketMessage" style="margin-top:16px;color:green"></div>
@endsection

@push('scripts')
<script>
const ticketId = {{ json_encode($ticketId) }};

function authHeader(){
    const token = localStorage.getItem('api_token');
    return token ? {'X-Auth-Token': token, 'Accept':'application/json'} : {'Accept':'application/json'};
}

async function fetchTicket(){
    const res = await fetch('/tickets/' + ticketId, {headers: authHeader()});
    if(res.status===401){ alert('Autenticação necessária. Faça login.'); window.location='/ui/login'; return; }
    if(!res.ok){ const j=await res.json(); alert(j.message || 'Erro a carregar ticket'); return; }
    const data = await res.json();
    const ticket = data.ticket;
    document.getElementById('ticketDetails').innerHTML = `
        <p><strong>ID:</strong> ${ticket.id}</p>
        <p><strong>Título:</strong> ${ticket.title}</p>
        <p><strong>Descrição:</strong> ${ticket.description}</p>
        <p><strong>Prioridade:</strong> ${ticket.priority}</p>
        <p><strong>Estado:</strong> ${ticket.status}</p>
        <p><strong>Equipamento:</strong> ${ticket.equipment ? ticket.equipment.name : 'Nenhum'}</p>
        <p><strong>Sala:</strong> ${ticket.room ? ticket.room.name : 'Nenhuma'}</p>
        <p><strong>Técnico atribuído:</strong> ${ticket.technician ? ticket.technician.name : 'Não atribuído'}</p>
        <p><strong>Aberto em:</strong> ${ticket.opened_at || 'N/A'}</p>
        <p><strong>Em progresso em:</strong> ${ticket.in_progress_at || 'N/A'}</p>
        <p><strong>Fechado em:</strong> ${ticket.closed_at || 'N/A'}</p>
        <p><strong>Reaberto em:</strong> ${ticket.reopened_at || 'N/A'}</p>
    `;
}

async function fetchComments(){
    const res = await fetch('/tickets/' + ticketId + '/comments', {headers: authHeader()});
    if(res.status===401){ alert('Autenticação necessária. Faça login.'); window.location='/ui/login'; return; }
    if(!res.ok){ document.getElementById('commentsSection').innerText = 'Erro a carregar comentários'; return; }
    const data = await res.json();
    const section = document.getElementById('commentsSection');
    if(!data.comments.length){
        section.innerHTML = '<p>Nenhum comentário registado.</p>';
        return;
    }
    section.innerHTML = '<ul>' + data.comments.map(c => `<li><strong>${c.user ? c.user.name : 'Técnico'}:</strong> ${c.comment} <em>(${c.created_at})</em></li>`).join('') + '</ul>';
}

async function showMessage(message, error = false){
    const el = document.getElementById('ticketMessage');
    el.style.color = error ? 'red' : 'green';
    el.innerText = message;
    setTimeout(() => { el.innerText = ''; }, 5000);
}

async function postComment(event){
    event.preventDefault();
    const comment = document.getElementById('commentText').value.trim();
    if(!comment){ showMessage('Escreva um comentário antes de enviar.', true); return; }
    const res = await fetch('/tickets/' + ticketId + '/comments', {
        method: 'POST',
        headers: Object.assign({'Content-Type':'application/json'}, authHeader()),
        body: JSON.stringify({comment}),
    });
    const data = await res.json();
    if(!res.ok){ showMessage(data.message || JSON.stringify(data), true); return; }
    document.getElementById('commentText').value = '';
    await fetchComments();
    showMessage('Comentário adicionado com sucesso.');
}

async function assignTechnician(manual){
    const payload = {};
    if(manual){
        const technicianId = document.getElementById('assignTechnicianId').value;
        if(!technicianId){ showMessage('Informe o técnico para atribuição manual.', true); return; }
        payload.technician_id = parseInt(technicianId, 10);
    }

    const res = await fetch('/tickets/' + ticketId + '/assign-technician', {
        method: 'POST',
        headers: Object.assign({'Content-Type':'application/json'}, authHeader()),
        body: JSON.stringify(payload),
    });
    const data = await res.json();
    if(!res.ok){ showMessage(data.message || JSON.stringify(data), true); return; }
    await fetchTicket();
    showMessage('Técnico atribuído com sucesso.');
}

async function reopenTicket(){
    const res = await fetch('/tickets/' + ticketId + '/reopen', {
        method: 'POST',
        headers: Object.assign({'Content-Type':'application/json'}, authHeader()),
    });
    const data = await res.json();
    if(!res.ok){ showMessage(data.message || JSON.stringify(data), true); return; }
    await fetchTicket();
    showMessage('Ticket reaberto com sucesso.');
}

window.addEventListener('load', () => {
    fetchTicket();
    fetchComments();
    document.getElementById('commentForm').addEventListener('submit', postComment);
    document.getElementById('btnAssignManual').addEventListener('click', () => assignTechnician(true));
    document.getElementById('btnAssignAuto').addEventListener('click', () => assignTechnician(false));
    document.getElementById('btnReopen').addEventListener('click', reopenTicket);
});
</script>
@endpush
