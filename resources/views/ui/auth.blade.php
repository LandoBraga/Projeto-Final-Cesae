@extends('ui.layout')

@section('content')

<body class="min-h-screen overflow-hidden bg-[var(--bg)] text-[var(--text)] antialiased">

<div class="relative flex min-h-screen items-center justify-center overflow-hidden px-6 py-8 lg:px-10">

    {{-- Background --}}
    <div class="absolute inset-0 -z-30">

        <div class="absolute inset-0 bg-[var(--bg)]"></div>
        <div class="absolute left-1/2 top-0 h-[700px] w-[700px] -translate-x-1/2 rounded-full bg-primary/10 blur-[180px]"></div>
        <div class="absolute bottom-[-180px] right-[-120px] h-[520px] w-[520px] rounded-full bg-blue-500/10 blur-[180px]"></div>
        <div class="absolute top-40 left-[-120px] h-[460px] w-[460px] rounded-full bg-orange-500/10 blur-[160px]"></div>

        <div
            class="absolute inset-0 opacity-[0.03] dark:opacity-[0.06]"
            style="
                background-image:
                    linear-gradient(var(--border) 1px, transparent 1px),
                    linear-gradient(90deg,var(--border) 1px,transparent 1px);
                background-size:48px 48px;
            ">
        </div>

        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(250,204,21,0.08),transparent_45%)]"></div>

    </div>

    <div class="relative w-full max-w-7xl">

        <div
            class="
                overflow-hidden
                rounded-[34px]
                border
                border-[var(--border)]
                bg-[var(--surface)]
                shadow-2xl
                shadow-black/10
                dark:shadow-black/40
                backdrop-blur-xl
            ">

            <div class="h-1 w-full bg-primary"></div>

            <div class="grid lg:grid-cols-[1.08fr_0.92fr] min-h-[760px]">

                {{-- Painel Institucional --}}
                <aside class="relative flex flex-col justify-between overflow-hidden border-r border-[var(--border)] bg-[var(--surface-2)] p-12">

                    <div class="pointer-events-none absolute inset-0">
                        <div class="absolute -top-20 -left-20 h-72 w-72 rounded-full bg-primary/10 blur-3xl"></div>
                        <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-blue-500/5 blur-3xl"></div>
                    </div>

                    <div class="relative z-10">

                        <div class="flex items-center gap-4">

                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary text-black shadow-lg shadow-primary/20">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 00-1 1v1a2 2 0 11-4 0v-1a1 1 0 00-1-1H7a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 100-4H7a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                                </svg>
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.22em] text-primary">Enterprise Platform</p>
                                <h1 class="mt-1 text-2xl font-bold tracking-tight">Gestão de Avarias</h1>
                            </div>

                        </div>

                        <div class="mt-14">

                            <span class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-primary">
                                <span class="h-2 w-2 rounded-full bg-primary animate-pulse"></span>
                                Plataforma Online
                            </span>

                            <h2 class="mt-8 max-w-xl text-5xl font-extrabold leading-tight tracking-tight">Gestão inteligente de equipamentos industriais.</h2>

                            <p class="mt-8 max-w-xl text-base leading-8 text-[var(--text-soft)]">
                                Centralize tickets, equipamentos, técnicos, auditorias, relatórios, manutenção preventiva
                                e documentação da API numa única plataforma moderna para ambientes industriais.
                            </p>

                        </div>

                        <div class="mt-14 grid grid-cols-3 gap-5">
                            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-5">
                                <p class="text-3xl font-black text-primary">24/7</p>
                                <p class="mt-2 text-xs uppercase tracking-wider text-[var(--text-soft)]">Disponibilidade</p>
                            </div>
                            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-5">
                                <p class="text-3xl font-black text-primary">API</p>
                                <p class="mt-2 text-xs uppercase tracking-wider text-[var(--text-soft)]">RESTful</p>
                            </div>
                            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-5">
                                <p class="text-3xl font-black text-primary">SLA</p>
                                <p class="mt-2 text-xs uppercase tracking-wider text-[var(--text-soft)]">Controlo</p>
                            </div>
                        </div>

                    </div>

                    <div class="relative z-10 mt-12 space-y-5">
                        <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface)] p-6">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-semibold">Gestão Operacional</p>
                                    <p class="mt-3 text-sm leading-7 text-[var(--text-soft)]">
                                        Monitorize tickets, estados, prioridades, intervenções técnicas e histórico completo das ocorrências.
                                    </p>
                                </div>
                                <div class="rounded-xl bg-primary/10 px-3 py-2 text-xs font-bold text-primary">LIVE</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-5">
                                <p class="font-semibold">Equipamentos</p>
                                <p class="mt-2 text-xs leading-6 text-[var(--text-soft)]">Inventário completo.</p>
                            </div>
                            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-5">
                                <p class="font-semibold">Analytics</p>
                                <p class="mt-2 text-xs leading-6 text-[var(--text-soft)]">Indicadores em tempo real.</p>
                            </div>
                            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-5">
                                <p class="font-semibold">Auditoria</p>
                                <p class="mt-2 text-xs leading-6 text-[var(--text-soft)]">Histórico completo.</p>
                            </div>
                            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-5">
                                <p class="font-semibold">Swagger API</p>
                                <p class="mt-2 text-xs leading-6 text-[var(--text-soft)]">Documentação integrada.</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-2xl border border-primary/20 bg-primary/10 px-5 py-4">
                            <div>
                                <p class="font-semibold">Sistema operacional</p>
                                <p class="text-xs text-[var(--text-soft)] mt-1">Todos os serviços ativos.</p>
                            </div>
                            <span class="flex items-center gap-2 text-sm font-semibold text-primary">
                                <span class="h-3 w-3 rounded-full bg-primary animate-pulse"></span>
                                Online
                            </span>
                        </div>
                    </div>

                </aside>

                {{-- Painel de Autenticação --}}
                <section class="relative flex flex-col justify-center p-10 lg:p-14">

                    <div class="pointer-events-none absolute inset-0 overflow-hidden">
                        <div class="absolute -right-32 top-10 h-72 w-72 rounded-full bg-primary/5 blur-3xl"></div>
                        <div class="absolute bottom-0 left-0 h-60 w-60 rounded-full bg-blue-500/5 blur-3xl"></div>
                    </div>

                    <div class="relative z-10 mx-auto w-full max-w-md">

                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-4 py-2">
                            <span class="h-2 w-2 rounded-full bg-primary animate-pulse"></span>
                            <span class="text-[11px] font-bold uppercase tracking-[0.22em] text-primary">Área Segura</span>
                        </div>

                        <div class="mb-10">
                            <h2 class="text-4xl font-black tracking-tight">Bem-vindo.</h2>
                            <p class="mt-4 text-[15px] leading-7 text-[var(--text-soft)]">
                                Inicie sessão para aceder ao painel de gestão, acompanhar ocorrências,
                                consultar equipamentos e administrar toda a plataforma.
                            </p>
                        </div>

                        <div class="mb-10 rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-1.5">
                            <div class="flex">
                                <button id="tabLogin" type="button" class="flex-1 rounded-xl py-3 text-sm font-semibold transition-all duration-200">Iniciar Sessão</button>
                                <button id="tabRegister" type="button" class="flex-1 rounded-xl py-3 text-sm font-semibold transition-all duration-200">Criar Conta</button>
                            </div>
                        </div>

                        <div class="mb-8 flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-5 py-4">
                            <div>
                                <p class="text-sm font-semibold">Autenticação protegida</p>
                                <p class="mt-1 text-xs text-[var(--text-soft)]">As credenciais são transmitidas através de ligação segura.</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10">
                                <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2V10a2 2 0 00-2-2h-1V7a5 5 0 10-10 0v1H6a2 2 0 00-2 2v9a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>

                        <div id="msg" aria-live="polite" class="hidden mt-5 min-h-[42px] rounded-2xl text-center text-sm font-medium flex items-center justify-center transition-all"></div>

                        {{-- Form Login --}}
                        <form id="loginForm" class="space-y-6">

                            <div>
                                <label for="loginEmail" class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-[var(--text-soft)]">Endereço de Email</label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                        <svg class="h-5 w-5 text-[var(--text-soft)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 12H8m8-4H8m10-3H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2z"/>
                                        </svg>
                                    </div>
                                    <input id="loginEmail" name="email" type="email" autocomplete="email" required placeholder="utilizador@empresa.pt"
                                           class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] py-3.5 pl-12 pr-4 text-sm transition-all duration-200 outline-none placeholder:text-[var(--text-soft)] focus:border-primary focus:ring-4 focus:ring-primary/15">
                                </div>
                            </div>

                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label for="loginPassword" class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--text-soft)]">Palavra-passe</label>
                                    <button type="button" id="togglePassword" class="text-xs font-semibold text-primary transition hover:opacity-70">Mostrar</button>
                                </div>

                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                        <svg class="h-5 w-5 text-[var(--text-soft)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m6-6V9a6 6 0 10-12 0v2m12 0H6m12 0a2 2 0 012 2v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5a2 2 0 012-2"/>
                                        </svg>
                                    </div>
                                    <input id="loginPassword" name="password" type="password" autocomplete="current-password" required placeholder="••••••••"
                                           class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] py-3.5 pl-12 pr-12 text-sm transition-all duration-200 outline-none placeholder:text-[var(--text-soft)] focus:border-primary focus:ring-4 focus:ring-primary/15">

                                    <button type="button" id="passwordEye" class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--text-soft)] transition hover:text-primary">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <label class="flex cursor-pointer items-center gap-3">
                                    <input type="checkbox" class="h-4 w-4 rounded border-[var(--border)] text-primary focus:ring-primary">
                                    <span class="text-sm text-[var(--text-soft)]">Manter sessão iniciada</span>
                                </label>
                                <a href="#" class="text-sm font-medium text-primary transition hover:opacity-70">Recuperar acesso</a>
                            </div>

                            <button type="submit" class="group inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-primary px-6 py-3.5 text-sm font-bold text-black shadow-lg shadow-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-primary/30 active:translate-y-0">
                                Entrar no Sistema
                                <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>

                        </form>

                        {{-- Form Register (criar conta) --}}
                        <form id="registerForm" class="hidden space-y-6">

                            <div>
                                <label for="registerName" class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-[var(--text-soft)]">Nome</label>
                                <input id="registerName" name="name" type="text" autocomplete="name" required placeholder="Nome completo"
                                       class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] py-3.5 px-4 text-sm transition-all duration-200 outline-none placeholder:text-[var(--text-soft)] focus:border-primary focus:ring-4 focus:ring-primary/15">
                            </div>

                            <div>
                                <label for="registerEmail" class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-[var(--text-soft)]">Endereço de Email</label>
                                <input id="registerEmail" name="email" type="email" autocomplete="email" required placeholder="utilizador@empresa.pt"
                                       class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] py-3.5 px-4 text-sm transition-all duration-200 outline-none placeholder:text-[var(--text-soft)] focus:border-primary focus:ring-4 focus:ring-primary/15">
                            </div>

                            <div>
                                <label for="registerPassword" class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-[var(--text-soft)]">Palavra-passe</label>
                                <input id="registerPassword" name="password" type="password" autocomplete="new-password" required placeholder="mín. 8 caracteres"
                                       class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] py-3.5 px-4 text-sm transition-all duration-200 outline-none placeholder:text-[var(--text-soft)] focus:border-primary focus:ring-4 focus:ring-primary/15">
                            </div>

                            <div>
                                <label for="registerPasswordConfirmation" class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-[var(--text-soft)]">Confirmar palavra-passe</label>
                                <input id="registerPasswordConfirmation" name="password_confirmation" type="password" autocomplete="new-password" required placeholder="••••••••"
                                       class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] py-3.5 px-4 text-sm transition-all duration-200 outline-none placeholder:text-[var(--text-soft)] focus:border-primary focus:ring-4 focus:ring-primary/15">
                            </div>

                            <button type="submit" class="group inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-primary px-6 py-3.5 text-sm font-bold text-black shadow-lg shadow-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-primary/30 active:translate-y-0">
                                Criar Conta
                                <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>

                        </form>

                    </div>

                </section>

            </div>

        </div>

    </div>

</div>

<script>
(function () {
    const tabLogin = document.getElementById('tabLogin');
    const tabRegister = document.getElementById('tabRegister');

    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    const loginEmail = document.getElementById('loginEmail');
    const loginPassword = document.getElementById('loginPassword');

    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordEyeBtn = document.getElementById('passwordEye');

    const msg = document.getElementById('msg');

    function setMsg(message, type) {
        const isError = type === 'error';
        msg.classList.remove('hidden');
        msg.className = 'mt-5 min-h-[42px] rounded-2xl text-center text-sm font-medium flex items-center justify-center transition-all ' +
            (isError
                ? 'border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/30 text-red-700 dark:text-red-400'
                : 'border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400');
        msg.textContent = message;
    }

    function setLoading(form, loading) {
        const btn = form?.querySelector('button[type="submit"]');
        if (!btn) return;
        btn.disabled = loading;
        if (loading) {
            btn.dataset.original = btn.innerHTML;
            btn.innerHTML = `
                <span class="inline-flex items-center gap-3">
                    <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-20"></circle>
                        <path fill="currentColor" class="opacity-90" d="M4 12a8 8 0 018-8V0A12 12 0 000 12h4z"></path>
                    </svg>
                    A autenticar...
                </span>
            `;
            btn.classList.add('opacity-80', 'cursor-not-allowed');
        } else {
            if (btn.dataset.original) btn.innerHTML = btn.dataset.original;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    }

    function setActiveTab(tab) {
        const isLogin = tab === 'login';

        if (tabLogin && tabRegister) {
            tabLogin.className = isLogin
                ? 'flex-1 rounded-xl bg-primary text-black py-3 font-bold shadow-lg shadow-primary/20 transition-all duration-200'
                : 'flex-1 rounded-xl py-3 font-semibold text-[var(--text-soft)] hover:text-[var(--text)] transition-all';

            tabRegister.className = !isLogin
                ? 'flex-1 rounded-xl bg-primary text-black py-3 font-bold shadow-lg shadow-primary/20 transition-all duration-200'
                : 'flex-1 rounded-xl py-3 font-semibold text-[var(--text-soft)] hover:text-[var(--text)] transition-all';
        }

        if (loginForm && registerForm) {
            if (isLogin) {
                registerForm.classList.add('hidden');
                loginForm.classList.remove('hidden');
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
            }
        }

        if (msg) msg.classList.add('hidden');
    }

    function togglePassword() {
        if (!loginPassword) return;
        const isPassword = loginPassword.type === 'password';
        loginPassword.type = isPassword ? 'text' : 'password';

        if (togglePasswordBtn) togglePasswordBtn.textContent = isPassword ? 'Ocultar' : 'Mostrar';

        if (passwordEyeBtn) {
            passwordEyeBtn.innerHTML = isPassword
                ? `
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.59A2 2 0 0012 14a2 2 0 001.41-.59M9.88 5.09A9.77 9.77 0 0112 5c7 0 11 7 11 7a21.66 21.66 0 01-5.08 5.91M6.1 6.1A21.55 21.55 0 001 12s4 7 11 7a10.7 10.7 0 005.02-1.22"/>
                    </svg>
                `
                : `
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                `;
        }
    }

    tabLogin?.addEventListener('click', () => setActiveTab('login'));
    tabRegister?.addEventListener('click', () => setActiveTab('register'));

    togglePasswordBtn?.addEventListener('click', togglePassword);
    passwordEyeBtn?.addEventListener('click', togglePassword);

    document.addEventListener('DOMContentLoaded', () => {
        setActiveTab('login');
    });

    loginForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!loginEmail || !loginPassword) return;

        setLoading(loginForm, true);
        setMsg('A verificar credenciais no servidor...', 'success');

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const res = await fetch('/login', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
                },
                body: JSON.stringify({
                    email: loginEmail.value,
                    password: loginPassword.value
                })
            });

            const j = await res.json().catch(() => ({}));

            if (res.status !== 200) {
                setMsg(j.message || 'Credenciais inválidas.', 'error');
                setLoading(loginForm, false);
                return;
            }

            if (j.token) {
                document.cookie = `api_token=${j.token}; path=/; max-age=2592000; SameSite=Lax`;
                try { localStorage.setItem('api_token', j.token); } catch (e) {}
            }

            setMsg('Autenticação bem-sucedida! A redirecionar...', 'success');
            setLoading(loginForm, false);

            setTimeout(() => { window.location.href = '/ui'; }, 500);
        } catch (err) {
            setMsg('Falha crítica na comunicação com o servidor.', 'error');
            setLoading(loginForm, false);
        }
    });

    registerForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const name = document.getElementById('registerName')?.value;
        const email = document.getElementById('registerEmail')?.value;
        const password = document.getElementById('registerPassword')?.value;
        const password_confirmation = document.getElementById('registerPasswordConfirmation')?.value;

        if (!name || !email || !password || !password_confirmation) {
            setMsg('Preenche todos os campos para criar conta.', 'error');
            return;
        }

        setLoading(registerForm, true);
        setMsg('A criar conta...', 'success');

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const res = await fetch('/register', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
                },
                body: JSON.stringify({
                    name,
                    email,
                    password,
                    password_confirmation
                })
            });

            const j = await res.json().catch(() => ({}));

            if (res.status !== 201) {
                const errors = j.errors || {};
                const firstError = Object.values(errors)[0]?.[0];
                setMsg(firstError || j.message || 'Falha ao criar conta.', 'error');
                setLoading(registerForm, false);
                return;
            }

            // auto-login após register (token/cookie devolvidos pelo controller)
            if (j.token) {
                document.cookie = `api_token=${j.token}; path=/; max-age=2592000; SameSite=Lax`;
                try { localStorage.setItem('api_token', j.token); } catch (e) {}
            }

            setMsg('Conta criada com sucesso! A redirecionar...', 'success');
            setLoading(registerForm, false);
            setTimeout(() => { window.location.href = '/ui'; }, 600);

        } catch (err) {
            setMsg('Falha crítica na comunicação com o servidor.', 'error');
            setLoading(registerForm, false);
        }
    });
})();
</script>

<style>
/* Mantém animações existentes; bloco CSS original foi removido/evitado para não quebrar render */
</style>

</body>

@endsection

