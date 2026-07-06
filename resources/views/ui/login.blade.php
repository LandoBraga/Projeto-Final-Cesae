@extends('ui.layout')

@section('content')
<h2>Iniciar sessão</h2>
<form id="loginForm">
    <label>Endereço de email: <input name="email" type="email"></label><br>
    <label>Palavra-passe: <input name="password" type="password"></label><br>
    <button type="submit">Entrar</button>
</form>
<div id="msg"></div>
@endsection

@push('scripts')
<script>
// Processa o formulário de início de sessão com o token CSRF da página.
document.getElementById('loginForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = e.target;
    const data = {email: form.email.value, password: form.password.value};
    const res = await fetch('/login', {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify(data)});
    if(res.status!==200){ const j=await res.json(); document.getElementById('msg').innerText = j.message || JSON.stringify(j); return; }
    const j = await res.json();
    localStorage.setItem('api_token', j.token);
    document.getElementById('msg').innerText = 'Sessão iniciada com sucesso.';
    window.location = '/ui';
});
</script>
@endpush
