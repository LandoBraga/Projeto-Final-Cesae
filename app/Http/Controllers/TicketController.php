<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\AIService;
use Illuminate\Http\Request;

class AdminTicketController extends Controller
{
    protected $aiService;

    // Injeção de dependência do serviço de Inteligência Artificial
    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Exibe o detalhe do ticket injetando a sugestão em tempo real da IA
     */
    public function show($id)
    {
        // Procura o ticket trazendo os relacionamentos exatos do teu projeto
        $ticket = Ticket::with(['equipment.category', 'room', 'user'])->findOrFail($id);

        // Invoca o motor de Inteligência Artificial passando o objeto Ticket
        $recomendacaoIA = $this->aiService->recomendarTecnico($ticket);

        // Envia os dados para a vossa view centralizada na pasta 'ui'
        return view('ui.ticketDetail', compact('ticket', 'recomendacaoIA'));
    }

    /**
     * Grava a alocação do técnico sugerido pela IA ou escolhido manualmente
     */
    public function atribuirTecnico(Request $request, $id)
    {
        $request->validate([
            'tecnico_id' => 'required|exists:users,id', // Valida se o ID existe na tabela users
        ]);

        $ticket = Ticket::findOrFail($id);

        // Vai buscar dinamicamente o ID do estado "Em Curso" definido no teu Model
        $inProgressStatusId = Ticket::getStatusIdByName(Ticket::STATUS_IN_PROGRESS);
        $ticket->status_id  = $inProgressStatusId;
        $ticket->assigned_to    = $user->id;
        $ticket->in_progress_at = now();
        $ticket->save();

        // Notificamos o criador para manter o fluxo visível em tempo real e por email.
        if ($ticket->user && $ticket->user->email) {
            $ticket->user->notify(new TicketStatusChanged($ticket, $oldStatus, Ticket::STATUS_IN_PROGRESS));
        }

        event(new TicketStatusUpdatedBroadcast($ticket, $oldStatus, Ticket::STATUS_IN_PROGRESS));

        return response()->json(['ticket' => $ticket]);
    }

    /**
     * Associa explicitamente um técnico a um ticket (Apenas Administradores).
     */
    public function assignTechnician(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_ADMIN,
        ]);

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        $data = $request->only(['technician_id']);
        $validator = Validator::make($data, [
            'technician_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $technician = null;
        if (!empty($data['technician_id'])) {
            $technician = User::find($data['technician_id']);
            if (!$technician || !$technician->isTechnician()) {
                return response()->json(['message' => 'Técnico inválido'], 422);
            }
        } else {
            $technician = Ticket::getLeastBusyTechnician();
            if (!$technician) {
                return response()->json(['message' => 'Não existem técnicos disponíveis'], 422);
            }
        }

        $ticket->assigned_to = $technician->id;
        $ticket->save();

        return response()->json(['ticket' => $ticket]);
    }

    /**
     * Reabre um ticket que tenha sido previamente fechado.
     */
    public function reopenTicket(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
        ]);

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        if (!$ticket->reopen()) {
            return response()->json(['message' => 'Só é possível reabrir tickets fechados'], 422);
        }

        return response()->json(['ticket' => $ticket]);
    }

    /**
     * Adiciona um comentário técnico ou de progresso ao ticket.
     */
    public function cancelTicket(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);

        if (!$user->isCommon()) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        if ($ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        if (!$ticket->hasStatus(Ticket::STATUS_OPEN)) {
            return response()->json(['message' => 'Só é possível cancelar tickets abertos'], 403);
        }

        $cancelledStatusId = Ticket::getStatusIdByName(Ticket::STATUS_CANCELLED);
        $ticket->status_id = $cancelledStatusId;
        $ticket->closed_at = now();
        $ticket->save();

        return response()->json(['ticket' => $ticket]);
    }

    public function addComment(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        // Regra de autorização:
        // - ROLE_TECHNICIAN / ROLE_ADMIN: podem comentar qualquer ticket.
        // - ROLE_USER (common): só podem comentar o próprio ticket.
        if ($user->isCommon() && (int)$ticket->user_id !== (int)$user->id) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        // Valida role adicional apenas para common.
        if (!$user->isCommon()) {
            $this->requireRole($user, [
                User::ROLE_TECHNICIAN,
                User::ROLE_ADMIN,
            ]);
        }

        $data = $request->only(['comment']);
        $validator = Validator::make($data, [
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'comment'   => $data['comment'],
        ]);

        return response()->json(['comment' => $comment], 201);
    }

    /**
     * Lista todos os comentários associados a um determinado ticket.
     */
    public function listComments(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
        ]);

        $ticket = Ticket::with(['comments.user'])->find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        return response()->json(['comments' => $ticket->comments]);
    }

    /**
     * Faz o upload de um anexo fotográfico ou evidência para o ticket.
     */
    public function uploadPhoto(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        // Regra de autorização:
        // - ROLE_USER (common): só podem fazer upload no próprio ticket.
        // - ROLE_TECHNICIAN / ROLE_ADMIN: podem anexar em qualquer ticket.
        if ($user->isCommon() && (int) $ticket->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo' => ['required', 'file', 'image', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('photo');
        $path = $file->store('ticket_photos', 'public');
        $url = Storage::disk('public')->url($path);

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'file_name' => $file->getClientOriginalName(),
            'path'      => $path,
            'mime_type' => $file->getClientMimeType(),
            'size'      => $file->getSize(),
        ]);

        return response()->json([
            'attachment' => $attachment,
            'url' => $url,
        ], 201);
    }

    /**
     * Lista os anexos multimédia carregados no âmbito do ticket.
     */
    public function listPhotos(Request $request, int $id)
    {
        $this->authenticatedUser($request);

        $ticket = Ticket::with('attachments')->find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        return response()->json(['attachments' => $ticket->attachments]);
    }

    /**
     * Conclui de forma definitiva um ticket em curso, registando tempos e custos operacionais.
     */
    public function closeTicket(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
        ]);

        // Executa a atualização no MySQL com as colunas reais: assigned_to e status_id
        $ticket->update([
            'assigned_to'    => $request->tecnico_id,
            'status_id'      => $inProgressStatusId,
            'in_progress_at' => now(), // Regista o início do carimbo temporal
        ]);

        // Dispara o evento de Broadcast nativo que vocês já têm para atualizar os ecrãs
        $oldStatus = Ticket::STATUS_OPEN;
        event(new \App\Events\TicketStatusUpdatedBroadcast($ticket, $oldStatus, Ticket::STATUS_IN_PROGRESS));

        // Envia notificação por email para o utilizador que abriu o problema
        if ($ticket->user && $ticket->user->email) {
            $ticket->user->notify(new \App\Notifications\TicketStatusChanged($ticket, $oldStatus, Ticket::STATUS_IN_PROGRESS));
        }

        return redirect()->route('admin.tickets.show', $id)
                         ->with('success', 'Técnico alocado com sucesso via Assistente IA!');
    }
}