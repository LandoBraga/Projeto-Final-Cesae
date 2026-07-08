@extends('ui.layout')

@push('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
@endpush

@push('scripts-top')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales/pt.min.js'></script>
<style>
    .fc { color: #e2e8f0; }
    .fc-toolbar-title { color: #f8fafc; }
    .fc-button { background-color: #0f172a !important; border-color: #334155 !important; color: #e2e8f0 !important; }
    .fc-button:hover { background-color: #1e293b !important; }
    .fc-daygrid-day-number, .fc-col-header-cell-cushion { color: #cbd5e1; }
    .fc-day-today { background-color: rgba(34,211,238,0.15) !important; }
    .fc-event { background-color: #0891b2 !important; border-color: #0891b2 !important; }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto max-w-7xl px-6 py-8 lg:px-8">
        <div class="mb-6 flex items-center justify-between rounded-2xl border border-white/10 bg-slate-900/80 px-5 py-4 shadow-2xl shadow-slate-950/40 backdrop-blur">
            <div>
                <p class="text-sm font-medium text-cyan-300">Agenda</p>
                <h1 class="text-2xl font-semibold text-white">Intervenções agendadas</h1>
            </div>
            <a href="/ui" class="rounded-full border border-cyan-400/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-300 transition hover:bg-cyan-500/20">← Voltar ao painel</a>
        </div>
        <div class="rounded-3xl border border-white/10 bg-slate-900/80 p-4 shadow-2xl shadow-slate-950/40 backdrop-blur lg:p-6">
            <div id='calendar'></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (!isAuthenticated()) {
        window.location.href = '/ui/login';
        return;
    }
    
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'pt',
        initialView: 'dayGridMonth',
        buttonText: {
            today: 'Hoje',
            month: 'Mês',
            week: 'Semana',
            day: 'Dia',
            list: 'Lista'
        },
        allDayText: 'Todo o dia',
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('/calendar/events', {
                headers: authHeader()
            })
            .then(function(response) {
                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = '/ui/login';
                        return null;
                    }
                    throw new Error('Erro ao carregar eventos');
                }
                return response.json();
            })
            .then(function(events) {
                if (!events) return;
                successCallback(events);
            })
            .catch(function(err) {
                console.error(err);
                failureCallback(err);
            });
        },
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        eventClick: function(info) {
            alert('Ticket: ' + info.event.title + '\nInício: ' + info.event.start + '\nFim: ' + info.event.end);
        }
    });
    calendar.render();
});
</script>
@endpush
