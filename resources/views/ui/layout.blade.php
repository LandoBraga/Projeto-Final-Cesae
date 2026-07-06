<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gestão de Avarias - Painel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(34,211,238,0.16),_transparent_25%),linear-gradient(135deg,_#020617_0%,_#111827_100%)]">
        <nav class="border-b border-white/10 bg-slate-900/75 backdrop-blur">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-6 py-4 lg:px-8">
                <div class="flex flex-wrap items-center gap-2 text-sm font-medium text-slate-300">
                    <a href="/" class="rounded-full px-3 py-2 transition hover:bg-white/10 hover:text-white">Início</a>
                    <a href="/ui" class="rounded-full px-3 py-2 transition hover:bg-white/10 hover:text-white">Painel</a>
                    <a href="/ui/tickets" class="rounded-full px-3 py-2 transition hover:bg-white/10 hover:text-white">Tickets</a>
                    <a href="/ui/equipments" class="rounded-full px-3 py-2 transition hover:bg-white/10 hover:text-white">Equipamentos</a>
                    <a href="/ui/users" class="rounded-full px-3 py-2 transition hover:bg-white/10 hover:text-white">Utilizadores</a>
                    <a href="/ui/audits" class="rounded-full px-3 py-2 transition hover:bg-white/10 hover:text-white">Auditoria</a>
                    <a href="/calendar" class="rounded-full px-3 py-2 transition hover:bg-white/10 hover:text-white">Agenda</a>
                    <a href="/docs/openapi" class="rounded-full px-3 py-2 transition hover:bg-white/10 hover:text-white">Swagger</a>
                </div>
                <div id="authBox" class="text-sm"></div>
            </div>
        </nav>

        <main class="mx-auto max-w-7xl px-6 py-8 lg:px-8">
            @yield('content')
        </main>
    </div>

<script>
function authHeader(){
    const token = localStorage.getItem('api_token');
    const headers = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    };
    if (token) {
        headers['X-Auth-Token'] = token;
    }
    return headers;
}

function renderAuthBox(){
    const box = document.getElementById('authBox');
    const token = localStorage.getItem('api_token');
    if(token){
        box.innerHTML = '<button onclick="logout()" class="rounded-full border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm font-medium text-rose-300 transition hover:bg-rose-500/20">Terminar sessão</button>';
    } else {
        box.innerHTML = '<a href="/ui/login" class="rounded-full border border-cyan-400/30 bg-cyan-500/10 px-3 py-2 text-sm font-medium text-cyan-300 transition hover:bg-cyan-500/20">Iniciar sessão</a>';
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
