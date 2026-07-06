<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function stats(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_ADMIN,
            $user::ROLE_TECHNICIAN,
        ]);

        $tickets = Ticket::where('status', Ticket::STATUS_CLOSED)->get();
        $averageResolution = $tickets->average(function (Ticket $ticket) {
            if (!$ticket->opened_at || !$ticket->closed_at) {
                return null;
            }
            return $ticket->closed_at->diffInMinutes($ticket->opened_at);
        });

        $openTickets = Ticket::where('status', Ticket::STATUS_OPEN)->get();
        $averageWaiting = $openTickets->average(function (Ticket $ticket) {
            if (!$ticket->opened_at) {
                return null;
            }
            return $ticket->opened_at->diffInMinutes(now());
        });

        return response()->json([
            'average_resolution_minutes' => round($averageResolution ?: 0, 2),
            'average_waiting_minutes' => round($averageWaiting ?: 0, 2),
        ]);
    }

    /**
     * Export tickets estatísticos/listagem em formato CSV (compatível com Excel).
     */
    public function exportCsv(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_ADMIN,
            $user::ROLE_TECHNICIAN,
        ]);

        $tickets = Ticket::all();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tickets_report.csv"',
        ];

        $callback = function () use ($tickets) {
            $handle = fopen('php://output', 'w');
            // Cabeçalho CSV
            fputcsv($handle, ['id','title','status','opened_at','in_progress_at','closed_at','minutes_spent','cost','budget_status','budget_amount']);

            foreach ($tickets as $t) {
                fputcsv($handle, [
                    $t->id,
                    $t->title,
                    $t->status,
                    optional($t->opened_at)->toDateTimeString(),
                    optional($t->in_progress_at)->toDateTimeString(),
                    optional($t->closed_at)->toDateTimeString(),
                    $t->minutes_spent,
                    $t->cost,
                    $t->budget_status,
                    $t->budget_amount,
                ]);
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Exporta relatório de tickets em PDF usando uma vista Blade simples e DOMPDF.
     */
    public function exportPdf(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_ADMIN,
            $user::ROLE_TECHNICIAN,
        ]);

        $tickets = Ticket::all();

        $pdf = PDF::loadView('reports.tickets', ['tickets' => $tickets]);

        return $pdf->download('tickets_report.pdf');
    }
}
