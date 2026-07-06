<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiDocsController extends Controller
{
    public function swagger(Request $request)
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Gestão de Avarias API',
                'version' => '1.0.0',
                'description' => 'Documentação OpenAPI para tickets, equipamentos, salas e relatórios.',
            ],
            'paths' => [
                '/tickets' => [
                    'get' => [
                        'summary' => 'Listar tickets',
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                    'post' => [
                        'summary' => 'Criar ticket',
                        'responses' => ['201' => ['description' => 'Criado']],
                    ],
                ],
                '/analytics' => [
                    'get' => [
                        'summary' => 'Obter métricas',
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                ],
            ],
        ];

        return response()->json($spec);
    }
}
