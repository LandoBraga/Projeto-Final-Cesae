@extends('ui.layout')

@section('content')
@component('ui.partials.page-card', [
    'title' => 'Detalhes do Ticket',
    'subtitle' => 'Consulte o estado do ticket e adicione comentários.',
    'actions' => '<a href="/ui/tickets" class="rounded-full border border-cyan-400/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-300 transition hover:bg-cyan-500/20">← Voltar ao painel</a>'
])
    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5">
            <div id="ticketDetails" class="space-y-2 text-sm text-slate-300">
                <p>Carregando...</p>
            </div>
        </div>
        <div class="space-y-6">
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5">
                <h3 class="text-lg font-semibold text-white">Comentários internos</h3>
                <div id="commentsSection" class="mt-4 text-sm text-slate-300">
                    <p>Carregando comentários...</p>
                </div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5">
                <h3 class="text-lg font-semibold text-white">Adicionar comentário</h3>
                <form id="commentForm" class="mt-4 space-y-3">
                    <textarea id="commentText" rows="4" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500" placeholder="Escreva um comentário para outros técnicos..."></textarea>
                    <button type="submit" class="rounded-xl bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400">Enviar comentário</button>
                </form>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5">
                <h3 class="text-lg font-semibold text-white">Fotografias</h3>
                <form id="photoForm" class="mt-4 space-y-3">
                    <input id="photoInput" type="file" accept="image/*" class="block w-full text-sm text-slate-300">
                    <button type="submit" class="rounded-xl bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400">Enviar fotografia</button>
                </form>
                <div id="photosSection" class="mt-4 text-sm text-slate-300">
                    <p>Nenhuma fotografia carregada.</p>
                </div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5">
                <h3 class="text-lg font-semibold text-white">Ações</h3>
                <div class="mt-4 space-y-3">
                    <label class="block text-sm text-slate-300">Técnico ID para atribuição manual
                        <input id="assignTechnicianId" type="number" min="1" class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button id="btnAssignManual" class="rounded-xl border border-cyan-500/30 bg-cyan-500/10 px-3 py-2 text-sm font-semibold text-cyan-300 transition hover:bg-cyan-500/20">Atribuir Técnico</button>
                        <button id="btnAssignAuto" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10">Atribuir Automático</button>
                    </div>
                    <button id="btnReopen" class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm font-semibold text-amber-300 transition hover:bg-amber-500/20">Reabrir Ticket</button>
                </div>
            </div>
        </div>
    </div>
    <div id="ticketMessage" class="mt-4 min-h-6 text-sm text-emerald-400"></div>
@endcomponent
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

async function fetchPhotos(){
    const res = await fetch('/tickets/' + ticketId + '/photos', {headers: authHeader()});
    if(!res.ok) return;
    const data = await res.json();
    const section = document.getElementById('photosSection');
    if(!data.attachments || !data.attachments.length){
        section.innerHTML = '<p>Nenhuma fotografia carregada.</p>';
        return;
    }
    section.innerHTML = data.attachments.map((a) => `<div class="mt-2 rounded-xl border border-white/10 bg-slate-900/60 p-2"><p class="font-medium text-white">${a.file_name}</p><p class="text-xs text-slate-400">${a.mime_type || ''}</p></div>`).join('');
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

async function uploadPhoto(event){
    event.preventDefault();
    const input = document.getElementById('photoInput');
    if(!input.files.length){ showMessage('Selecione uma fotografia antes de enviar.', true); return; }
    const formData = new FormData();
    formData.append('photo', input.files[0]);
    const res = await fetch('/tickets/' + ticketId + '/photos', {
        method: 'POST',
        headers: authHeader(),
        body: formData,
    });
    const data = await res.json();
    if(!res.ok){ showMessage(data.message || JSON.stringify(data), true); return; }
    input.value = '';
    await fetchPhotos();
    showMessage('Fotografia enviada com sucesso.');
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
    fetchPhotos();
    document.getElementById('commentForm').addEventListener('submit', postComment);
    document.getElementById('photoForm').addEventListener('submit', uploadPhoto);
    document.getElementById('btnAssignManual').addEventListener('click', () => assignTechnician(true));
    document.getElementById('btnAssignAuto').addEventListener('click', () => assignTechnician(false));
    document.getElementById('btnReopen').addEventListener('click', reopenTicket);
});
</script>
@endpush
