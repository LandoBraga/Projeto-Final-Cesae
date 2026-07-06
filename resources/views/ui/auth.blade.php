<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestão de Avarias - Entrar</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.22),_transparent_35%),linear-gradient(135deg,_#020617_0%,_#111827_100%)]">
        <div class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-6 py-16 lg:px-8">
            <div class="grid w-full overflow-hidden rounded-3xl border border-white/10 bg-slate-900/80 shadow-2xl shadow-slate-950/50 backdrop-blur lg:grid-cols-[1.05fr_0.95fr]">
                <div class="flex flex-col justify-between bg-gradient-to-br from-cyan-500/20 via-slate-900/80 to-slate-950 p-8 lg:p-12">
                    <div>
                        <p class="mb-4 inline-flex rounded-full border border-cyan-400/30 bg-cyan-400/10 px-3 py-1 text-sm font-medium text-cyan-300">Sistema de apoio técnico</p>
                        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Acompanhe avarias, tickets e equipamentos num só lugar.</h1>
                        <p class="mt-4 max-w-xl text-base text-slate-300">Uma experiência simples e moderna para técnicos e administradores gerir ocorrências com rapidez.</p>
                    </div>
                    <div class="mt-8 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Tickets</p>
                            <p class="mt-1 text-sm text-slate-300">Acompanhe estado, prioridade e histórico.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Agenda</p>
                            <p class="mt-1 text-sm text-slate-300">Organize intervenções e prazos de forma eficiente.</p>
                        </div>
                    </div>
                </div>

                <div class="p-8 lg:p-12">
                    <div class="mb-6 flex rounded-full bg-slate-800 p-1">
                        <button id="tabLogin" class="flex-1 rounded-full px-4 py-2 text-sm font-medium text-slate-200 transition-all bg-cyan-500/20 text-cyan-300">Entrar</button>
                        <button id="tabRegister" class="flex-1 rounded-full px-4 py-2 text-sm font-medium text-slate-400 transition-all hover:text-slate-200">Criar conta</button>
                    </div>

                    <form id="loginForm" class="space-y-4">
                        <div>
                            <label for="loginEmail" class="mb-2 block text-sm font-medium text-slate-300">Email</label>
                            <input id="loginEmail" name="email" type="email" required class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-white outline-none ring-0 focus:border-cyan-500" placeholder="nome@empresa.pt">
                        </div>
                        <div>
                            <label for="loginPassword" class="mb-2 block text-sm font-medium text-slate-300">Password</label>
                            <input id="loginPassword" name="password" type="password" required class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-white outline-none ring-0 focus:border-cyan-500" placeholder="••••••••">
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-cyan-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400">Entrar na plataforma</button>
                    </form>

                    <form id="registerForm" class="hidden space-y-4">
                        <div>
                            <label for="registerName" class="mb-2 block text-sm font-medium text-slate-300">Nome</label>
                            <input id="registerName" name="name" type="text" required class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-white outline-none ring-0 focus:border-cyan-500" placeholder="João Silva">
                        </div>
                        <div>
                            <label for="registerEmail" class="mb-2 block text-sm font-medium text-slate-300">Email</label>
                            <input id="registerEmail" name="email" type="email" required class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-white outline-none ring-0 focus:border-cyan-500" placeholder="joao@empresa.pt">
                        </div>
                        <div>
                            <label for="registerPassword" class="mb-2 block text-sm font-medium text-slate-300">Password</label>
                            <input id="registerPassword" name="password" type="password" required class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-white outline-none ring-0 focus:border-cyan-500" placeholder="Mínimo 8 caracteres">
                        </div>
                        <button type="submit" class="w-full rounded-xl border border-cyan-500/40 bg-cyan-500/10 px-4 py-3 text-sm font-semibold text-cyan-300 transition hover:bg-cyan-500/20">Criar conta</button>
                    </form>

                    <p class="mt-4 text-sm text-slate-400">Já tem conta? Use a opção <span class="font-medium text-cyan-300">Entrar</span>.</p>
                    <div id="authMessage" class="mt-4 min-h-6 text-sm"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const tabLogin = document.getElementById('tabLogin');
        const tabRegister = document.getElementById('tabRegister');
        const messageBox = document.getElementById('authMessage');

        function setActiveTab(tab) {
            const isLogin = tab === 'login';
            tabLogin.className = isLogin ? 'flex-1 rounded-full bg-cyan-500/20 px-4 py-2 text-sm font-medium text-cyan-300 transition-all' : 'flex-1 rounded-full px-4 py-2 text-sm font-medium text-slate-400 transition-all hover:text-slate-200';
            tabRegister.className = isLogin ? 'flex-1 rounded-full px-4 py-2 text-sm font-medium text-slate-400 transition-all hover:text-slate-200' : 'flex-1 rounded-full bg-cyan-500/20 px-4 py-2 text-sm font-medium text-cyan-300 transition-all';
            loginForm.classList.toggle('hidden', !isLogin);
            registerForm.classList.toggle('hidden', isLogin);
        }

        function showMessage(text, isError = false) {
            messageBox.className = isError ? 'mt-4 min-h-6 text-sm text-rose-400' : 'mt-4 min-h-6 text-sm text-emerald-400';
            messageBox.textContent = text;
        }

        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const payload = {
                email: document.getElementById('loginEmail').value,
                password: document.getElementById('loginPassword').value
            };

            const response = await fetch('/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();

            if (!response.ok) {
                showMessage(data.message || 'Não foi possível iniciar sessão.', true);
                return;
            }

            localStorage.setItem('api_token', data.token);
            showMessage('Sessão iniciada com sucesso.');
            window.location.href = '/ui';
        });

        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const payload = {
                name: document.getElementById('registerName').value,
                email: document.getElementById('registerEmail').value,
                password: document.getElementById('registerPassword').value
            };

            const response = await fetch('/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();

            if (!response.ok) {
                showMessage(data.message || 'Não foi possível criar a conta.', true);
                return;
            }

            localStorage.setItem('api_token', data.token);
            showMessage('Conta criada com sucesso.');
            window.location.href = '/ui';
        });

        tabLogin.addEventListener('click', () => setActiveTab('login'));
        tabRegister.addEventListener('click', () => setActiveTab('register'));
        setActiveTab('login');
    </script>
</body>
</html>
