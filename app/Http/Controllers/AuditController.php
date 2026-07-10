<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuditController extends Controller
{
    /**
     * Lista os registos de auditoria do sistema.
     * Protegido globalmente via web.php com os middlewares custom.auth e role:admin.
     */
    #[OA\Get(
        path: '/admin/audits',
        tags: ['Admin'],
        summary: 'Listar auditoria',
        security: [['X-Auth-Token' => []], ['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de auditoria')
        ]
    )]
    public function index(Request $request)
    {
        // A paginação protege a performance quando o histórico começar a crescer bastante.
        $audits = Audit::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50); // O frontend recebe metadados úteis para navegação entre páginas.

        return response()->json(['audits' => $audits]);
    }
}
