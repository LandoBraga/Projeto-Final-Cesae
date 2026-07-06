<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestão de Avarias - Painel</title>
    <style>
        body{font-family: Arial, Helvetica, sans-serif; margin:20px}
        nav a{margin-right:12px}
        table{border-collapse:collapse;width:100%}
        th,td{border:1px solid #ccc;padding:8px}
        .login{float:right}
    </style>
</head>
<body>
    <nav>
        <a href="/">Início</a>
        <a href="/ui">Dashboard</a>
        <a href="/ui/tickets">Tickets</a>
        <a href="/ui/equipments">Equipamentos</a>
        <a href="/ui/users">Utilizadores</a>
        <a href="/ui/audits">Auditoria</a>
        <a href="/calendar">Agenda</a>
        <span class="login" id="authBox"></span>
    </nav>
    <hr />
    <div id="content">
        @yield('content')
    </div>

<script>
function authHeader(){
    const token = localStorage.getItem('api_token');
    return token ? {'X-Auth-Token': token, 'Accept':'application/json'} : {'Accept':'application/json'};
}

function renderAuthBox(){
    const box = document.getElementById('authBox');
    const token = localStorage.getItem('api_token');
    if(token){
        box.innerHTML = '<button onclick="logout()">Logout</button>';
    } else {
        box.innerHTML = '<a href="/ui/login">Login</a>';
    }
}

function logout(){
    const token = localStorage.getItem('api_token');
    if(!token) return;
    fetch('/logout', {method:'POST', headers: Object.assign({'Content-Type':'application/json'}, authHeader())})
    .finally(()=>{localStorage.removeItem('api_token'); renderAuthBox(); window.location='/ui';});
}

renderAuthBox();
</script>

@stack('scripts')
</body>
</html>
