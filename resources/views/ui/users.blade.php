@extends('ui.layout')

@section('content')
<h2>Utilizadores</h2>
<table id="usersTable"><thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Perfil</th><th>Ativo</th></tr></thead><tbody></tbody></table>
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
