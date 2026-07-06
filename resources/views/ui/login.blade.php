@extends('ui.layout')

@section('content')
<h2>Login</h2>
<form id="loginForm">
    <label>Email: <input name="email" type="email"></label><br>
    <label>Password: <input name="password" type="password"></label><br>
    <button type="submit">Login</button>
</form>
<div id="msg"></div>
@endsection

@push('scripts')
<script>
document.getElementById('loginForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = e.target;
    const data = {email: form.email.value, password: form.password.value};
    const res = await fetch('/login', {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify(data)});
    if(res.status!==200){ const j=await res.json(); document.getElementById('msg').innerText = j.message || JSON.stringify(j); return; }
    const j = await res.json();
    localStorage.setItem('api_token', j.token);
    document.getElementById('msg').innerText = 'Login successful';
    window.location = '/ui';
});
</script>
@endpush
