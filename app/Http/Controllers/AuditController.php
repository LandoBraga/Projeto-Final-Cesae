<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    /**
     * Lista os registos de auditoria do sistema.
     * Protegido globalmente via web.php com os middlewares custom.auth e role:admin.
     */
    public function index(Request $request)
    {
        // CORREÇÃO: Substituído o 'limit(200)->get()' por paginação paginada ('paginate').
        // Isto evita problemas de performance quando a tabela de logs contiver milhares de registos,
        // permitindo que o frontend consuma os dados em blocos (ex: 50 registos por página).
        $audits = Audit::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50); // Retorna uma estrutura paginada com metadados (page, total, etc.)

        return response()->json(['audits' => $audits]);
    }
}
