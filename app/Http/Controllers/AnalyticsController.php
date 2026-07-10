<?php

namespace App\Http\Controllers;

use App\Exports\TicketsExport;
use App\Models\Audit;
use App\Models\Ticket;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    /**
     * Obtém o payload completo do dashboard analítico para a interface web.
     */
    #[OA\Get(
        path: '/analytics/stats',
        tags: ['Analytics'],
        summary: 'Métricas gerais',
        security: [['X-Auth-Token' => []], ['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'KPIs agregados')
        ]
    )]
    public function stats(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
        ]);

        return response()->json($this->buildPayload());
    }

    /**
     * Fornece os dados para os gráficos do dashboard analítico.
     */
    #[OA\Get(
        path: '/analytics/charts',
        tags: ['Analytics'],
        summary: 'Dados para dashboards',
        security: [['X-Auth-Token' => []], ['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Séries para gráficos')
        ]
    )]
    public function charts(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
        ]);

        return response()->json($this->buildPayload());
    }

    private function buildPayload(): array
    {
        $openStatusId = Ticket::getStatusIdByName(Ticket::STATUS_OPEN);
        $inProgressStatusId = Ticket::getStatusIdByName(Ticket::STATUS_IN_PROGRESS);
        $closedStatusId = Ticket::getStatusIdByName(Ticket::STATUS_CLOSED);

        $tickets = Ticket::query()
            ->with(['equipment', 'room', 'technician'])
            ->get();

        $openTickets = $tickets->filter(fn ($ticket) => $ticket->status_id === $openStatusId);
        $inProgressTickets = $tickets->filter(fn ($ticket) => $ticket->status_id === $inProgressStatusId);
        $closedTickets = $tickets->filter(fn ($ticket) => $ticket->status_id === $closedStatusId && $ticket->opened_at && $ticket->closed_at);
        $budgetPendingTickets = $tickets->filter(fn ($ticket) => $ticket->budget_status === Ticket::BUDGET_PENDING);

        $averageResolutionMinutes = $closedTickets->map(function ($ticket) {
            return Carbon::parse($ticket->opened_at)->diffInMinutes(Carbon::parse($ticket->closed_at));
        })->avg() ?: 0;

        $averageWaitingMinutes = $tickets->filter(fn ($ticket) => $ticket->opened_at && $ticket->status_id !== $closedStatusId)
            ->map(function ($ticket) {
                return Carbon::parse($ticket->opened_at)->diffInMinutes(now());
            })->avg() ?: 0;

        $slaTargetMinutes = 480;
        $slaSuccess = $closedTickets->count() > 0
            ? round(
                ($closedTickets->filter(function ($ticket) {
                    $duration = Carbon::parse($ticket->opened_at)->diffInMinutes(Carbon::parse($ticket->closed_at));
                    return $duration <= 480;
                })->count() / $closedTickets->count()) * 100,
                1
            )
            : 100;

        $statusBreakdown = collect([
            ['label' => 'Abertos', 'value' => $openTickets->count()],
            ['label' => 'Em Curso', 'value' => $inProgressTickets->count()],
            ['label' => 'Pendente de Orçamento', 'value' => $budgetPendingTickets->count()],
            ['label' => 'Fechados', 'value' => $closedTickets->count()],
        ]);

        $priorityBreakdown = collect([
            ['label' => 'Baixa', 'value' => $tickets->filter(fn ($ticket) => $ticket->priority === Ticket::PRIORITY_LOW)->count()],
            ['label' => 'Média', 'value' => $tickets->filter(fn ($ticket) => $ticket->priority === Ticket::PRIORITY_MEDIUM)->count()],
            ['label' => 'Alta', 'value' => $tickets->filter(fn ($ticket) => $ticket->priority === Ticket::PRIORITY_HIGH)->count()],
        ]);

        $monthlyBuckets = $this->buildMonthlySeries($tickets, $openStatusId, $inProgressStatusId, $closedStatusId);

        $topEquipments = $tickets->filter(fn ($ticket) => $ticket->equipment)
            ->groupBy('equipment_id')
            ->map(fn ($group) => [
                'name' => optional($group->first()->equipment)->name ?? 'Sem equipamento',
                'total' => $group->count(),
                'subtitle' => 'intervenções',
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values();

        $topRooms = $tickets->filter(fn ($ticket) => $ticket->room)
            ->groupBy('room_id')
            ->map(fn ($group) => [
                'name' => optional($group->first()->room)->name ?? 'Sem sala',
                'total' => $group->count(),
                'subtitle' => 'tickets',
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values();

        $topTechnicians = $tickets->filter(fn ($ticket) => $ticket->technician)
            ->groupBy('assigned_to')
            ->map(fn ($group) => [
                'name' => optional($group->first()->technician)->name ?? 'Sem técnico',
                'total' => $group->count(),
                'subtitle' => 'ações',
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values();

        $recentActivity = Audit::query()
            ->with('user')
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($audit) {
                $userName = optional($audit->user)->name ?? 'Sistema';
                $description = match ($audit->event) {
                    'created' => 'Registou uma nova entrada no sistema.',
                    'updated' => 'Atualizou campos de um registo.',
                    'deleted' => 'Removou um registo do sistema.',
                    default => 'Ação registada na auditoria.',
                };

                return [
                    'title' => $userName,
                    'description' => $description,
                    'time' => $audit->created_at?->diffForHumans() ?? 'recentemente',
                ];
            })
            ->values();

        return [
            'average_resolution_minutes' => round($averageResolutionMinutes, 1),
            'average_waiting_minutes' => round($averageWaitingMinutes, 1),
            'open_tickets' => $openTickets->count(),
            'in_progress_tickets' => $inProgressTickets->count(),
            'waiting_budget_tickets' => $budgetPendingTickets->count(),
            'closed_tickets' => $closedTickets->count(),
            'system_availability' => 99.9,
            'sla_success' => $slaSuccess,
            'by_priority' => [
                'labels' => $priorityBreakdown->pluck('label')->values(),
                'data' => $priorityBreakdown->pluck('value')->values(),
            ],
            'ticket_status_breakdown' => [
                'labels' => $statusBreakdown->pluck('label')->values(),
                'data' => $statusBreakdown->pluck('value')->values(),
            ],
            'monthly_tickets' => [
                'labels' => $monthlyBuckets['labels'],
                'open' => $monthlyBuckets['open'],
                'in_progress' => $monthlyBuckets['in_progress'],
                'closed' => $monthlyBuckets['closed'],
            ],
            'monthly_cost' => [
                'labels' => $monthlyBuckets['cost_labels'],
                'data' => $monthlyBuckets['cost_data'],
            ],
            'top_equipments' => $topEquipments,
            'top_equipment' => $topEquipments,
            'top_rooms' => $topRooms,
            'top_technicians' => $topTechnicians,
            'recent_activity' => $recentActivity,
        ];
    }

    private function buildMonthlySeries(Collection $tickets, int $openStatusId, int $inProgressStatusId, int $closedStatusId): array
    {
        $months = [];
        $open = [];
        $inProgress = [];
        $closed = [];
        $costLabels = [];
        $costData = [];

        foreach (range(5, 0) as $offset) {
            $monthKey = now()->subMonths($offset)->format('Y-m');
            $months[] = $monthKey;
            $open[$monthKey] = 0;
            $inProgress[$monthKey] = 0;
            $closed[$monthKey] = 0;
            $costLabels[$monthKey] = 0;
        }

        foreach ($tickets as $ticket) {
            if (!$ticket->opened_at) {
                continue;
            }

            $monthKey = Carbon::parse($ticket->opened_at)->format('Y-m');
            if (!array_key_exists($monthKey, $open)) {
                continue;
            }

            if ($ticket->status_id === $openStatusId) {
                $open[$monthKey]++;
            }

            if ($ticket->status_id === $inProgressStatusId) {
                $inProgress[$monthKey]++;
            }

            if ($ticket->status_id === $closedStatusId) {
                $closed[$monthKey]++;

                if ($ticket->closed_at && $ticket->cost !== null) {
                    $costLabels[$monthKey] = ($costLabels[$monthKey] ?? 0) + (float) $ticket->cost;
                }
            }
        }

        return [
            'labels' => array_keys($open),
            'open' => array_values($open),
            'in_progress' => array_values($inProgress),
            'closed' => array_values($closed),
            'cost_labels' => array_keys($costLabels),
            'cost_data' => array_values($costLabels),
        ];
    }

    /**
     * Exporta o relatório de todos os tickets em formato de fluxo CSV (Streaming).
     */
    #[OA\Get(
        path: '/analytics/export/csv',
        tags: ['Analytics'],
        summary: 'Exportar CSV',
        security: [['X-Auth-Token' => []], ['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Ficheiro CSV descarregado')
        ]
    )]
    public function exportCsv(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
        ]);

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tickets_report.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id', 'title', 'status_id', 'opened_at', 'in_progress_at', 'closed_at', 'minutes_spent', 'cost', 'budget_status', 'budget_amount']);

            foreach (Ticket::cursor() as $ticket) {
                fputcsv($handle, [
                    $ticket->id,
                    $ticket->title,
                    $ticket->status_id,
                    optional($ticket->opened_at)->toDateTimeString(),
                    optional($ticket->in_progress_at)->toDateTimeString(),
                    optional($ticket->closed_at)->toDateTimeString(),
                    $ticket->minutes_spent,
                    $ticket->cost,
                    $ticket->budget_status,
                    $ticket->budget_amount,
                ]);
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Exporta o relatório de tickets em formato PDF via DOMPDF.
     */
    #[OA\Get(
        path: '/analytics/export/pdf',
        tags: ['Analytics'],
        summary: 'Exportar PDF',
        security: [['X-Auth-Token' => []], ['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Ficheiro PDF descarregado')
        ]
    )]
    public function exportPdf(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
        ]);

        $tickets = Ticket::select([
            'id', 'title', 'status_id', 'opened_at', 'in_progress_at',
            'closed_at', 'minutes_spent', 'cost', 'budget_status', 'budget_amount'
        ])->get();

        $pdf = PDF::loadView('reports.tickets', ['tickets' => $tickets]);

        return $pdf->download('tickets_report.pdf');
    }

    /**
     * Exporta o relatório de tickets em formato Excel (.xlsx).
     */
    #[OA\Get(
        path: '/analytics/export/excel',
        tags: ['Analytics'],
        summary: 'Exportar Excel',
        security: [['X-Auth-Token' => []], ['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Ficheiro XLSX descarregado')
        ]
    )]
    public function exportExcel(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
        ]);

        $filename = 'tickets_report_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new TicketsExport(), $filename);
    }
}
