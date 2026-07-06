<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestão de Avarias - Página Principal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(34,211,238,0.22),_transparent_30%),linear-gradient(135deg,_#020617_0%,_#111827_100%)]">
        <div class="mx-auto flex min-h-screen max-w-7xl flex-col justify-center px-6 py-16 lg:px-8">
            <div class="grid gap-8 rounded-3xl border border-white/10 bg-slate-900/80 p-8 shadow-2xl shadow-slate-950/50 backdrop-blur lg:grid-cols-[1.1fr_0.9fr] lg:p-12">
                <div>
                    <p class="inline-flex rounded-full border border-cyan-400/30 bg-cyan-400/10 px-3 py-1 text-sm font-medium text-cyan-300">Gestão operacional moderna</p>
                    <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">Centralize tickets, equipamentos e auditoria com um só clique.</h1>
                    <p class="mt-5 max-w-2xl text-lg text-slate-300">Uma página de entrada elegante para navegar por todas as áreas do sistema e entrar de forma rápida.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="/ui" class="rounded-full bg-cyan-500 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400">Abrir dashboard</a>
                        <a href="/ui/login" class="rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:bg-white/5">Entrar ou registar</a>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
                    <h2 class="text-xl font-semibold text-white">Acesso rápido</h2>
                    <div class="mt-5 grid gap-3">
                        <a href="/ui/tickets" class="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                            <p class="font-semibold text-white">Tickets</p>
                            <p class="mt-1 text-sm text-slate-400">Consultar, filtrar e abrir detalhes das ocorrências.</p>
                        </a>
                        <a href="/ui/equipments" class="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                            <p class="font-semibold text-white">Equipamentos</p>
                            <p class="mt-1 text-sm text-slate-400">Visualizar a frota de ativos e respetivas salas.</p>
                        </a>
                        <a href="/ui/users" class="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                            <p class="font-semibold text-white">Utilizadores</p>
                            <p class="mt-1 text-sm text-slate-400">Acompanhar contas e perfis da equipa.</p>
                        </a>
                        <a href="/ui/audits" class="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                            <p class="font-semibold text-white">Auditoria</p>
                            <p class="mt-1 text-sm text-slate-400">Rever alterações recentes e histórico das entidades.</p>
                        </a>
                        <a href="/calendar" class="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                            <p class="font-semibold text-white">Agenda</p>
                            <p class="mt-1 text-sm text-slate-400">Abrir a vista de calendário para planeamento.</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
