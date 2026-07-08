<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Room;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Controller que trata operações relacionadas com `Ticket`.
     *
     * Regras gerais aplicadas em cada endpoint:
     * - Autenticação via `X-Auth-Token` ou Bearer Token (ver Controller::authenticatedUser)
     * - Verificação de papel/permissão com `requireRole`
     */

    /**
     * Cria um novo ticket de avaria.
     * - Apenas utilizadores comuns (`ROLE_USER`) podem criar.
     * - Valida título/descrição e a atividade do equipamento/sala.
     */
    public function store(Request $request)
    {
        // Alterado de $request->user() para o método centralizado da API
        // para garantir a integridade da sessão baseada no token customizado.
        $user = $this->authenticatedUser($request);

        $data = $request->only(['title', 'description', 'equipment_id', 'room_id', 'priority']);

        // Validação dos campos recebidos pelo pedido
        $validator = Validator::make($data, [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string'],
            'equipment_id' => ['nullable', 'integer', 'exists:equipments,id'],
            'room_id'      => ['nullable', 'integer', 'exists:rooms,id'],
            'priority'     => ['nullable', 'string', 'in:baixa,média,alta,crítica'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Se for informado um equipamento, valida que existe e está ativo
        if (!empty($data['equipment_id'])) {
            $equipment = Equipment::find($data['equipment_id']);
            if (!$equipment || !$equipment->active) {
                return response()->json(['message' => 'Equipamento inválido ou inativo.'], 422);
            }
        }

        // Se for informada uma sala, valida que existe e está ativa
        if (!empty($data['room_id'])) {
            $room = Room::find($data['room_id']);
            if (!$room || !$room->active) {
                return response()->json(['message' => 'Sala inválida ou inativa.'], 422);
            }
        }

        $openStatusId = Ticket::getStatusIdByName(Ticket::STATUS_OPEN);
        
        $ticket = Ticket::create([
            'user_id'      => $user->id,
            'equipment_id' => $data['equipment_id'] ?? null,
            'room_id'      => $data['room_id'] ?? null,
            'title'        => $data['title'],
            'description'  => $data['description'],
            'priority'     => $data['priority'] ?? Ticket::PRIORITY_MEDIUM,
            'status_id'    => $openStatusId,
            'opened_at'    => now(),
        ]);

        if ($user->email) {
            Mail::raw('Novo ticket criado: ' . $ticket->title, function ($message) use ($user, $ticket) {
                $message->to($user->email)->subject('Novo ticket registado #' . $ticket->id);
            });
        }

        return response()->json(['ticket' => $ticket], 201);
    }

    /**
     * Lista todos os tickets aplicando os filtros definidos.
     * - Utilizadores normais veem exclusivamente os seus próprios tickets.
     * - Técnicos e Administradores têm visibilidade global sobre os registos.
     */
    public function index(Request $request)
    {
        $user = $this->authenticatedUser($request);

        $query = Ticket::query()->with(['equipment', 'room', 'technician', 'user']);

        // Filtro de scope: utilizadores comuns veem apenas os seus tickets
        if ($user->isCommon()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', intval($request->input('equipment_id')));
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', intval($request->input('room_id')));
        }

        if ($request->filled('technician_id')) {
            $query->where('assigned_to', intval($request->input('technician_id')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Substituído o 'get()' massivo por 'paginate()' para evitar quebras de memória
        // em base de dados de produção volumosas, facilitando a navegação do frontend por páginas.
        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json(['tickets' => $tickets]);
    }

    /**
     * Exibe os detalhes de um ticket específico.
     */
    public function show(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $ticket = Ticket::with(['equipment', 'room', 'technician', 'user', 'comments.user'])->find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        // Defesa contra quebra de privilégios (IDOR)
        if ($user->isCommon() && $ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        return response()->json(['ticket' => $ticket]);
    }

    /**
     * Retorna a lista de todos os tickets que se encontram em aberto.
     */
    public function openTickets(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
        ]);

        $openStatusId = Ticket::getStatusIdByName(Ticket::STATUS_OPEN);
        
        $tickets = Ticket::where('status_id', $openStatusId)
            ->with(['equipment', 'room', 'technician', 'user'])
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return response()->json(['tickets' => $tickets]);
    }

    /**
     * Atribui e inicia o atendimento de um ticket aberto pelo técnico autenticado.
     */
    public function startTicket(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
        ]);

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        if (!$ticket->hasStatus(Ticket::STATUS_OPEN)) {
            return response()->json(['message' => 'Só é possível iniciar tickets abertos'], 422);
        }

        $inProgressStatusId = Ticket::getStatusIdByName(Ticket::STATUS_IN_PROGRESS);
        $ticket->status_id = $inProgressStatusId;
        $ticket->assigned_to = $user->id;
        $ticket->in_progress_at = now();
        $ticket->save();

        return response()->json(['ticket' => $ticket]);
    }

    /**
     * Associa explicitamente um técnico a um ticket (Apenas Administradores).
     */
    public function assignTechnician(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_ADMIN,
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

        $ticket->assignToTechnician($technician);

        return response()->json(['ticket' => $ticket]);
    }

    /**
     * Reabre um ticket que tenha sido previamente fechado.
     */
    public function reopenTicket(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
            $user::ROLE_ADMIN,
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
    public function addComment(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
            $user::ROLE_ADMIN,
        ]);

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
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
            $user::ROLE_TECHNICIAN,
            $user::ROLE_ADMIN,
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

        $validator = Validator::make($request->all(), [
            'photo' => ['required', 'file', 'image', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path = $request->file('photo')->store('tickets', 'public');
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'file_name' => $request->file('photo')->getClientOriginalName(),
            'path'      => $path,
            'mime_type' => $request->file('photo')->getClientMimeType(),
            'size'      => $request->file('photo')->getSize(),
        ]);

        return response()->json(['attachment' => $attachment], 201);
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
            $user::ROLE_TECHNICIAN,
        ]);

        $data = $request->only(['minutes_spent', 'cost']);

        $validator = Validator::make($data, [
            'minutes_spent' => ['required', 'integer', 'min:1'],
            'cost'          => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        if (!$ticket->hasStatus(Ticket::STATUS_IN_PROGRESS)) {
            return response()->json(['message' => 'Só é possível encerrar tickets em curso'], 422);
        }

        $closedStatusId = Ticket::getStatusIdByName(Ticket::STATUS_CLOSED);
        $ticket->status_id = $closedStatusId;
        $ticket->minutes_spent = $data['minutes_spent'];
        $ticket->cost = $data['cost'];
        $ticket->closed_at = now();
        $ticket->save();

        return response()->json(['ticket' => $ticket]);
    }

    /**
     * Solicita aprovação de orçamento de reparação ao Administrador.
     */
    public function requestBudget(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
        ]);

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        if (!$ticket->hasStatus(Ticket::STATUS_IN_PROGRESS)) {
            return response()->json(['message' => 'Só é possível pedir orçamento para tickets em curso'], 422);
        }

        $data = $request->only(['threshold']);
        $threshold = isset($data['threshold']) ? floatval($data['threshold']) : 100.00;

        if ($ticket->cost === null && !$request->has('cost')) {
            return response()->json(['message' => 'Custo necessário para avaliar pedido de orçamento'], 422);
        }

        if ($request->has('cost')) {
            $ticket->cost = $request->input('cost');
            $ticket->save();
        }

        $requested = $ticket->requestBudgetAuthorization($threshold);

        if (!$requested) {
            return response()->json(['message' => 'Não foi necessário pedir autorização de orçamento'], 200);
        }

        return response()->json(['ticket' => $ticket]);
    }

    /**
     * Realiza a calendarização temporal de uma intervenção.
     */
    public function scheduleTicket(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
            $user::ROLE_ADMIN,
        ]);

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        $data = $request->only(['start', 'end', 'technician_id']);

        $validator = Validator::make($data, [
            'start'         => ['required', 'date'],
            'end'           => ['nullable', 'date', 'after_or_equal:start'],
            'technician_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket->scheduled_at = $data['start'];
        $ticket->scheduled_end = $data['end'] ?? null;
        $ticket->scheduled = true;

        if (!empty($data['technician_id'])) {
            $ticket->assigned_to = intval($data['technician_id']);
        }

        $ticket->save();

        return response()->json(['ticket' => $ticket]);
    }

    /**
     * Fornece os eventos estruturados em formato JSON compatível com o FullCalendar.
     */
    public function calendarEvents(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
            $user::ROLE_ADMIN,
        ]);

        $query = Ticket::query()->where('scheduled', true);

        if ($request->filled('technician_id')) {
            $query->where('assigned_to', intval($request->input('technician_id')));
        }

        $tickets = $query->get();

        $events = $tickets->map(function ($t) {
            return [
                'id'            => $t->id,
                'title'         => $t->title . ' (' . $t->status . ')',
                'start'         => optional($t->scheduled_at)->toIso8601String(),
                'end'           => optional($t->scheduled_end)->toIso8601String(),
                'technician_id' => $t->assigned_to,
            ];
        });

        return response()->json($events);
    }

    /**
     * Renderiza a vista principal do calendário (Blade).
     */
    public function calendarView(Request $request)
    {
        return view('calendar');
    }
}
