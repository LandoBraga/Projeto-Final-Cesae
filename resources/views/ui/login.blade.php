@extends('ui.layout')

@section('content')
<div class="relative min-h-[calc(100vh-80px)] overflow-hidden">

    {{-- Background Decorativo --}}
    <div class="absolute inset-0 -z-10">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(250,204,21,0.08),transparent_45%)] dark:bg-[radial-gradient(circle_at_top,rgba(250,204,21,0.12),transparent_45%)]"></div>

        <div class="absolute top-0 left-1/2 -translate-x-1/2 h-[420px] w-[420px] rounded-full bg-primary/10 blur-3xl"></div>

        <div class="absolute inset-0 opacity-[0.03] dark:opacity-[0.06]"
             style="background-image: linear-gradient(var(--border) 1px, transparent 1px),
                                    linear-gradient(90deg,var(--border) 1px,transparent 1px);
                    background-size:40px 40px;">
        </div>
    </div>

    <div class="flex items-center justify-center px-6 py-12">

        <div class="w-full max-w-lg">

            {{-- Branding --}}
            <div class="text-center mb-10">

                <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-primary shadow-lg shadow-primary/20 mb-6">

                    <svg class="w-10 h-10 text-black"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2.5"
                         viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 00-1 1v1a2 2 0 11-4 0v-1a1 1 0 00-1-1H7a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 100-4H7a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                    </svg>

                </div>

                <h1 class="text-4xl font-extrabold tracking-tight text-[var(--text)]">
                    Gestão de Avarias
                </h1>

                <p class="mt-3 text-[var(--text-soft)] max-w-sm mx-auto leading-relaxed">
                    Plataforma central para gestão, manutenção e acompanhamento de equipamentos.
                </p>

            </div>

            {{-- Card --}}
            <div class="relative rounded-3xl border border-[var(--border)] bg-[var(--surface)] shadow-xl shadow-black/5 dark:shadow-black/30 overflow-hidden">

                <div class="absolute inset-x-0 top-0 h-1 bg-primary"></div>

                <div class="p-10">

                    <div class="mb-8">

                        <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary mb-4">
                            Autenticação
                        </span>

                        <h2 class="text-3xl font-bold text-[var(--text)]">
                            Bem-vindo novamente
                        </h2>

                        <p class="mt-2 text-sm text-[var(--text-soft)]">
                            Introduza as suas credenciais para aceder ao painel administrativo.
                        </p>

                    </div>

                    <form id="loginForm" class="space-y-6">

                        <div>

                            <label class="block mb-2 text-xs font-bold uppercase tracking-wider text-[var(--text-soft)]">
                                Email
                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                required
                                placeholder="utilizador@empresa.pt"
                                class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-5 py-3.5 text-sm text-[var(--text)] outline-none transition-all duration-200 focus:border-primary focus:ring-4 focus:ring-primary/15">

                        </div>

                        <div>

                            <div class="flex items-center justify-between mb-2">

                                <label class="text-xs font-bold uppercase tracking-wider text-[var(--text-soft)]">
                                    Palavra-passe
                                </label>

                                <a href="#" class="text-sm font-medium text-primary hover:underline">
                                    Esqueceu-se?
                                </a>

                            </div>

                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                placeholder="••••••••"
                                class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-5 py-3.5 text-sm text-[var(--text)] outline-none transition-all duration-200 focus:border-primary focus:ring-4 focus:ring-primary/15">

                        </div>

                        <button
                            type="submit"
                            class="group w-full rounded-2xl bg-primary py-3.5 font-bold text-black transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-primary/20 active:translate-y-0">

                            <span class="flex items-center justify-center gap-2">

                                Entrar no Sistema

                                <svg class="w-4 h-4 transition-transform group-hover:translate-x-1"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2"
                                     viewBox="0 0 24 24">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M9 5l7 7-7 7"/>

                                </svg>

                            </span>

                        </button>

                    </form>

                    <div
                        id="msg"
                        aria-live="polite"
                        class="mt-5 min-h-[42px] rounded-2xl text-center text-sm font-medium flex items-center justify-center transition-all">
                    </div>

                </div>

            </div>

            {{-- Rodapé --}}
            <div class="mt-8 text-center">

                <p class="text-xs text-[var(--text-soft)]">
                    © {{ date('Y') }} Sistema de Gestão de Avarias
                </p>

                <p class="mt-1 text-xs text-[var(--text-soft)] opacity-70">
                    Desenvolvido em Laravel • Interface Responsiva • Light & Dark Mode
                </p>

            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const msgEl = document.getElementById('msg');

    // Estado: A verificar dados
    msgEl.className = 'mt-4 text-center text-xs font-semibold text-amber-600 dark:text-amber-400 animate-pulse';
    msgEl.innerText = 'A verificar credenciais no servidor...';

    const data = {
        email: form.email.value,
        password: form.password.value
    };

    try {
        const res = await fetch('/login', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });

        if (res.status !== 200) {
            const j = await res.json();
            msgEl.className = 'mt-4 text-center text-xs font-bold text-red-600 dark:text-red-400 p-3 bg-red-500/5 rounded-xl border border-red-500/10 animate-[fadeIn_0.2s_ease-out]';
            msgEl.innerText = j.message || 'Credenciais de acesso incorretas.';
            return;
        }

        const j = await res.json();
        localStorage.setItem('api_token', j.token);

        // Atualizar cookie de sessão de forma segura se aplicável
        document.cookie = `api_token=${j.token}; path=/; max-age=86400; SameSite=Lax`;

        msgEl.className = 'mt-4 text-center text-xs font-bold text-emerald-600 dark:text-emerald-400 p-3 bg-emerald-500/5 rounded-xl border border-emerald-500/10 animate-[fadeIn_0.2s_ease-out]';
        msgEl.innerText = 'Autenticação bem-sucedida! A redirecionar...';

        setTimeout(() => {
            window.location = '/ui';
        }, 500);

    } catch (err) {
        msgEl.className = 'mt-4 text-center text-xs font-bold text-red-600 dark:text-red-400 p-3 bg-red-500/5 rounded-xl border border-red-500/10';
        msgEl.innerText = 'Falha crítica na comunicação com o servidor.';
    }
});
</script>
@endpush
