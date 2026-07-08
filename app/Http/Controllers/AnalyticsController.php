<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    /**
     * Obtém métricas gerais de tickets fechados e tempos médios.
     * Otimizado para executar agregações diretamente na base de dados.
     */
    public function stats(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
        ]);
        
        $openStatusId = Ticket::getStatusIdByName(Ticket::STATUS_OPEN);
        $closedStatusId = Ticket::getStatusIdByName(Ticket::STATUS_CLOSED);
        
        // Cálculo de médias transferido integralmente para o SQL (evita carregar milhares de modelos em memória).
        // NOTA: A função TIMESTAMPDIFF é nativa do MySQL/MariaDB. Se estiveres a usar SQLite em ambiente de testes,
        // deverá ser adaptada para: AVG((strftime('%s', closed_at) - strftime('%s', opened_at)) / 60)
        $averageResolution = DB::table('tickets')
            ->where('status_id', $closedStatusId)
            ->whereNotNull('opened_at')
            ->whereNotNull('closed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, opened_at, closed_at)) as avg_res')
            ->value('avg_res');

        // Calcula o tempo médio que os tickets abertos estão em espera até ao momento atual (NOW())
        $averageWaiting = DB::table('tickets')
            ->where('status_id', $openStatusId)
            ->whereNotNull('opened_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, opened_at, NOW())) as avg_wait')
            ->value('avg_wait');

        // Utilização do método count() do construtor de consultas para realizar um "SELECT COUNT(*)" rápido
        $openTicketsCount = Ticket::where('status_id', $openStatusId)->count();
        $closedTicketsCount = Ticket::where('status_id', $closedStatusId)->count();

        return response()->json([
            'average_resolution_minutes' => round($averageResolution ?: 0, 2),
            'average_waiting_minutes' => round($averageWaiting ?: 0, 2),
            'open_tickets' => $openTicketsCount,
            'closed_tickets' => $closedTicketsCount,
        ]);
    }

    /**
     * Exporta o relatório de todos os tickets em formato de fluxo CSV (Streaming).
     * Otimizado para mitigar problemas de "Memory Limit Exceeded".
     */
    public function exportCsv(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
        ]);
        
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tickets_report.csv"',
        ];

        // Removeu-se a consulta 'Ticket::all()' de fora do scope.
        // O fluxo processará os dados diretamente de dentro do callback de streaming.
        $callback = function () {
            $handle = fopen('php://output', 'w');

            // Define a linha de cabeçalho do ficheiro CSV
            fputcsv($handle, ['id','title','status_id','opened_at','in_progress_at','closed_at','minutes_spent','cost','budget_status','budget_amount']);

            // O método cursor() utiliza PHP Generators por baixo do capô.
            // Mantém apenas 1 registo Eloquent de cada vez na memória do servidor, permitindo exportar
            // milhões de linhas sem estourar os recursos do PHP.
            foreach (Ticket::cursor() as $t) {
                fputcsv($handle, [
                    $t->id,
                    $t->title,
                    $t->status_id,
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
     * Exporta o relatório de tickets em formato PDF via DOMPDF.
     * Otimizado para aliviar a hidratação de propriedades desnecessárias do modelo.
     */
    public function exportPdf(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
        ]);
        
        // Em vez de submeter coleções completas e pesadas ao DOMPDF (que já consome muita memória),
        // filtramos estritamente as colunas necessárias que vão ser renderizadas na vista do relatório.
        $tickets = Ticket::select([
            'id', 'title', 'status_id', 'opened_at', 'in_progress_at',
            'closed_at', 'minutes_spent', 'cost', 'budget_status', 'budget_amount'
        ])->get();

        $pdf = PDF::loadView('reports.tickets', ['tickets' => $tickets]);

        return $pdf->download('tickets_report.pdf');
    }
}
