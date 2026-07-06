<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Room;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Controller que trata operações relacionadas com `Ticket`.
     *
     * Regras gerais aplicadas em cada endpoint:
     * - Autenticação via `X-Auth-Token` (ver Controller::authenticatedUser)
     * - Verificação de papel/permissão com `requireRole`
     */
    /**
     * Cria um novo ticket.
     * - Apenas utilizadores comuns (`ROLE_USER`) podem criar.
     * - Valida título/descrição e existência de equipamento/sala.
     */
    public function store(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_USER,
        ]);

        $data = $request->only(['title', 'description', 'equipment_id', 'room_id', 'priority']);

        // Validação dos campos recebidos pelo request
        $validator = Validator::make($data, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'equipment_id' => ['nullable', 'integer', 'exists:equipments,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'priority' => ['nullable', 'string', 'in:baixa,média,alta,crítica'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Se for informado um equipamento, valida que existe e está activo
        if (!empty($data['equipment_id'])) {
            $equipment = Equipment::find($data['equipment_id']);
            if (!$equipment || !$equipment->active) {
                return response()->json(['message' => 'Equipamento inválido ou inativo.'], 422);
            }
        }

        // Se for informada uma sala, valida que existe e está activa
        if (!empty($data['room_id'])) {
            $room = Room::find($data['room_id']);
            if (!$room || !$room->active) {
                return response()->json(['message' => 'Sala inválida ou inativa.'], 422);
            }
        }

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'equipment_id' => $data['equipment_id'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? Ticket::PRIORITY_MEDIUM,
            'status' => Ticket::STATUS_OPEN,
            'opened_at' => now(),
        ]);

        // Retorna o ticket criado com código HTTP 201
        return response()->json(['ticket' => $ticket], 201);
    }

    /**
     * Lista tickets.
     * - Utilizadores normais veem apenas os seus tickets.
     * - Técnicos e ADM veem todos.
     */
    public function index(Request $request)
    {
        $user = $this->authenticatedUser($request);

        $query = Ticket::query()->with(['equipment', 'room', 'technician', 'user']);

        // Simples: utilizadores comuns veem apenas os seus tickets; técnicos/ADM veem todos.
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

        $tickets = $query->get();

        // Retorna lista de tickets conforme permissões
        return response()->json(['tickets' => $tickets]);
    }

    public function show(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $ticket = Ticket::with(['equipment', 'room', 'technician', 'user', 'comments.user'])->find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        if ($user->isCommon() && $ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        return response()->json(['ticket' => $ticket]);
    }

    public function openTickets(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
            $user::ROLE_ADMIN,
        ]);

        $tickets = Ticket::where('status', Ticket::STATUS_OPEN)
            ->with(['equipment', 'room', 'technician', 'user'])
            ->get();

        return response()->json(['tickets' => $tickets]);
    }

    /**
     * Técnico inicia um ticket aberto.
     * - Verifica que o ticket está `aberta`.
     * - Atribui o técnico autenticado ao ticket e altera o estado para `em curso`.
     */
    public function startTicket(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
        ]);

    /**
     * Marca um ticket como 'em reparação' ao início da intervenção pelo técnico.
     * - Verifica permissões e regista auditoria.
     */
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        // Só é possível iniciar se estiver aberto
        if ($ticket->status !== Ticket::STATUS_OPEN) {
            return response()->json(['message' => 'Só é possível iniciar tickets abertos'], 422);
        }

        // Actualiza campos relevantes e grava
        $ticket->status = Ticket::STATUS_IN_PROGRESS;
        $ticket->assigned_to = $user->id;
        $ticket->in_progress_at = now();
        $ticket->save();

        return response()->json(['ticket' => $ticket]);
    }

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
            'user_id' => $user->id,
            'comment' => $data['comment'],
        ]);

        return response()->json(['comment' => $comment], 201);
    }

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
     * Técnico fecha um ticket em curso.
     * - Recebe `minutes_spent` e `cost` obrigatórios.
     * - Valida permissões e estado antes de fechar.
     */
    public function closeTicket(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
        ]);

    /**
     * Fecha um ticket pelo técnico.
     * - Se o custo for baixo, fecha diretamente; se for alto, solicita autorização.
     */

        $data = $request->only(['minutes_spent', 'cost']);

        // Validação dos dados enviados para encerramento
        $validator = Validator::make($data, [
            'minutes_spent' => ['required', 'integer', 'min:1'],
            'cost' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtém o ticket e valida existência/estado
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        if ($ticket->status !== Ticket::STATUS_IN_PROGRESS) {
            return response()->json(['message' => 'Só é possível encerrar tickets em curso'], 422);
        }

        // Grava informações de encerramento (minutos, custo e timestamp)
        $ticket->status = Ticket::STATUS_CLOSED;
        $ticket->minutes_spent = $data['minutes_spent'];
        $ticket->cost = $data['cost'];
        $ticket->closed_at = now();
        $ticket->save();

        return response()->json(['ticket' => $ticket]);
    }

    /**
     * Técnico pede autorização de orçamento para um ticket em curso.
     * - Aceita um parâmetro opcional `threshold` para comparar com `cost`.
     * - Se `cost` não estiver definido no ticket, pode ser enviado no request.
     */
    public function requestBudget(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
        ]);

    /**
     * Técnico solicita a criação de um orçamento para um ticket.
     * - Guarda valores propostos e marca estado para revisão pelo ADM.
     */
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        // Só tickets em curso podem ter pedidos de orçamento
        if ($ticket->status !== Ticket::STATUS_IN_PROGRESS) {
            return response()->json(['message' => 'Só é possível pedir orçamento para tickets em curso'], 422);
        }

        $data = $request->only(['threshold']);
        $threshold = isset($data['threshold']) ? floatval($data['threshold']) : 100.00;

        // Assegura que existe um custo para avaliação; pode ser enviado pelo request
        if ($ticket->cost === null && !$request->has('cost')) {
            return response()->json(['message' => 'Custo necessário para avaliar pedido de orçamento'], 422);
        }

        // Se for enviado o custo, actualiza o ticket antes da avaliação
        if ($request->has('cost')) {
            $ticket->cost = $request->input('cost');
            $ticket->save();
        }

        $requested = $ticket->requestBudgetAuthorization($threshold);

        // Se não foi necessário pedir autorização, devolve 200 com mensagem
        if (!$requested) {
            return response()->json(['message' => 'Não foi necessário pedir autorização de orçamento'], 200);
        }

        // Caso contrário, devolve o ticket actualizado com os campos de orçamento
        return response()->json(['ticket' => $ticket]);
    }

    /**
     * Agenda uma intervenção para o ticket (definir início/fim e opcionalmente atribuir técnico).
     * - Papéis: técnico ou ADM podem agendar.
     * - Parâmetros do request: `start` (datetime ISO), opcional `end`, opcional `technician_id`.
     */
    public function scheduleTicket(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
            $user::ROLE_ADMIN,
        ]);

      /**
       * Agenda uma intervenção para o ticket (data/hora e técnico associado).
       * - Utilizado para popular o calendário de intervenções.
       */
        $ticket = Ticket::find($id);

        if (! $ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        $data = $request->only(['start', 'end', 'technician_id']);

        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'start' => ['required', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
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
     * Return scheduled interventions as calendar events (JSON) compatible with FullCalendar.
     * - Roles: technician and admin.
     */
    public function calendarEvents(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
            $user::ROLE_ADMIN,
        ]);

    /**
     * Fornece eventos calendarizados (FullCalendar) no formato JSON.
     */

        // Apenas tickets com agendamento
        $query = Ticket::query();
        $query->where('scheduled', true);

        // Filtros opcionais: technician_id
        if ($request->filled('technician_id')) {
            $query->where('assigned_to', intval($request->input('technician_id')));
        }

        $tickets = $query->get();

        $events = $tickets->map(function ($t) {
            return [
                'id' => $t->id,
                'title' => $t->title . ' (' . $t->status . ')',
                'start' => optional($t->scheduled_at)->toIso8601String(),
                'end' => optional($t->scheduled_end)->toIso8601String(),
                'technician_id' => $t->assigned_to,
            ];
        });

        return response()->json($events);
    }

    /**
     * Renderiza a vista do calendário (Blade) que consome `/calendar/events`.
     */
    public function calendarView(Request $request)
    {
        return view('calendar');
    }
}
