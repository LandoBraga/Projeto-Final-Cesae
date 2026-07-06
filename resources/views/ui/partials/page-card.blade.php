{{-- Componente reutilizável para apresentar um cartão de conteúdo com cabeçalho e ações. --}}
<div class="rounded-3xl border border-white/10 bg-slate-900/80 p-6 shadow-2xl shadow-slate-950/40 backdrop-blur">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-white">{{ $title ?? 'Painel' }}</h1>
            @if(!empty($subtitle))
                <p class="mt-1 text-sm text-slate-400">{{ $subtitle }}</p>
            @endif
        </div>
        @if(!empty($actions))
            <div class="flex flex-wrap gap-2">
                {!! $actions !!}
            </div>
        @endif
    </div>
    <div class="mt-6">
        {{ $slot }}
    </div>
</div>
