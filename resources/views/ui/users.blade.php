@extends('ui.layout')

@section('content')
@component('ui.partials.page-card', [
    'title' => 'Utilizadores',
    'subtitle' => 'Consulta os utilizadores e respetivos perfis.',
    'actions' => '<a href="/ui" class="rounded-full border border-cyan-400/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-300 transition hover:bg-cyan-500/20">← Voltar ao painel</a>'
])
    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-white/10 bg-slate-900/70 p-4 md:flex-row md:items-end">
        <div class="flex-1">
            <label for="usersSearch" class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Pesquisa</label>
            <input id="usersSearch" type="text" placeholder="Pesquisar por nome, email ou perfil" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-white placeholder-slate-500 outline-none focus:border-cyan-500">
        </div>
        <div class="w-full md:w-44">
            <label for="usersRole" class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Perfil</label>
            <select id="usersRole" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
                <option value="">Todos</option>
            </select>
        </div>
        <div class="w-full md:w-44">
            <label for="usersStatus" class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Estado</label>
            <select id="usersStatus" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
                <option value="">Todos</option>
                <option value="active">Ativos</option>
                <option value="inactive">Inativos</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-950/60">
        <table id="usersTable" class="min-w-full divide-y divide-white/10 text-sm text-slate-300">
            <thead class="bg-slate-900/80 text-left text-slate-200"><tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Nome</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Perfil</th><th class="px-4 py-3">Ativo</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
@endcomponent
@endsection

@push('scripts')
<script>
let allUsers = [];

function isUserActive(user) {
    return user.active === true || user.active === 1 || user.active === '1' || String(user.active).toLowerCase() === 'true';
}

function getUserRole(user) {
    return user.role || user.profile || '';
}

function renderUsers() {
    const searchValue = document.getElementById('usersSearch').value.toLowerCase();
    const roleValue = document.getElementById('usersRole').value;
    const statusValue = document.getElementById('usersStatus').value;
    const tbody = document.querySelector('#usersTable tbody');
    tbody.innerHTML = '';

    const filtered = allUsers.filter((user) => {
        const haystack = `${user.id} ${user.name || ''} ${user.email || ''} ${getUserRole(user)}`.toLowerCase();
        const matchesSearch = !searchValue || haystack.includes(searchValue);
        const matchesRole = !roleValue || getUserRole(user).toLowerCase() === roleValue.toLowerCase();
        const matchesStatus = !statusValue || (statusValue === 'active' && isUserActive(user)) || (statusValue === 'inactive' && !isUserActive(user));
        return matchesSearch && matchesRole && matchesStatus;
    });

    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Nenhum utilizador encontrado.</td></tr>';
        return;
    }

    for (const user of filtered) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="px-4 py-3">${user.id}</td><td class="px-4 py-3">${user.name || ''}</td><td class="px-4 py-3">${user.email || ''}</td><td class="px-4 py-3">${getUserRole(user)}</td><td class="px-4 py-3">${isUserActive(user) ? 'Sim' : 'Não'}</td>`;
        tbody.appendChild(tr);
    }
}

async function loadUsers(){
    const res = await fetch('/admin/users', {headers: authHeader()});
    if(res.status===401){ alert('Autenticação necessária.'); window.location='/ui/login'; return; }
    const data = await res.json();
    allUsers = data.users || [];

    const roleSelect = document.getElementById('usersRole');
    const roles = [...new Set(allUsers.map((user) => getUserRole(user)).filter(Boolean))].sort();
    roleSelect.innerHTML = '<option value="">Todos</option>' + roles.map((role) => `<option value="${role}">${role}</option>`).join('');

    renderUsers();
}

window.addEventListener('load', () => {
    document.getElementById('usersSearch').addEventListener('input', renderUsers);
    document.getElementById('usersRole').addEventListener('change', renderUsers);
    document.getElementById('usersStatus').addEventListener('change', renderUsers);
    loadUsers();
});
</script>
@endpush
