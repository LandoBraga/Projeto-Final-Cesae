<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Lista as notificações pertencentes ao utilizador autenticado.
     */
    public function index(Request $request)
    {
        // Obtém com segurança a instância do utilizador com base no token fornecido
        $user = $this->authenticatedUser($request);

        // Isto permite ao utilizador aceder a todo o histórico de alertas e mensagens através do frontend,
        // dividindo os registos em blocos (ex: 15 notificações por página) de forma eficiente.
        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15); // Fornece dados paginados juntamente com metadados estruturados

        return response()->json(['notifications' => $notifications]);
    }

    /**
     * Marca uma notificação específica como lida, validando a respetiva propriedade.
     */
    public function markAsRead(Request $request, int $id)
    {
        // Obtém com segurança a instância do utilizador com base no token fornecido
        $user = $this->authenticatedUser($request);

        // Garante que o utilizador apenas consegue encontrar
        // e alterar notificações que lhe pertencem diretamente (scope por 'user_id').
        $notification = Notification::where('user_id', $user->id)->find($id);
        if (!$notification) {
            return response()->json(['message' => 'Notificação não encontrada'], 404);
        }

        // Atualiza o estado da notificação para lida
        $notification->is_read = true;
        $notification->save();

        return response()->json(['notification' => $notification]);
    }
}
