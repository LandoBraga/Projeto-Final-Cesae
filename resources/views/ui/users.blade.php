@extends('ui.layout')

@section('content')
@component('ui.partials.page-card', [
    'title' => 'Utilizadores',
    'subtitle' => 'Consulta os utilizadores e respetivos perfis.',
    'actions' => '<a href="/ui" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/10">Voltar atrás</a>'
])
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
async function loadUsers(){
    const res = await fetch('/admin/users', {headers: authHeader()});
    if(res.status===401){ alert('Autenticação necessária.'); window.location='/ui/login'; return; }
    const data = await res.json();
    const tbody = document.querySelector('#usersTable tbody'); tbody.innerHTML='';
    for(const u of data.users){
        const tr=document.createElement('tr');
        tr.innerHTML = `<td>${u.id}</td><td>${u.name}</td><td>${u.email}</td><td>${u.role}</td><td>${u.active}</td>`;
        tbody.appendChild(tr);
    }
}
window.addEventListener('load', loadUsers);
</script>
@endpush
