<?php

namespace App\Http\Controllers;

use App\Mail\TicketCreated;
use App\Models\Equipment;
use App\Models\Room;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use App\Events\TicketCreatedBroadcast;
use App\Events\TicketStatusUpdatedBroadcast;
use App\Notifications\TicketStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

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
    #[OA\Post(
        path: '/tickets',
        tags: ['Tickets'],
        summary: 'Criar ticket',
        security: [['X-Auth-Token' => []], ['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Projetor não liga'),
                    new OA\Property(property: 'description', type: 'string', example: 'O projetor da sala 4 não responde.'),
                    new OA\Property(property: 'equipment_id', type: 'integer', nullable: true, example: 12),
                    new OA\Property(property: 'room_id', type: 'integer', nullable: true, example: 3),
                    new OA\Property(property: 'priority', type: 'string', example: 'alta'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ticket criado'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function store(Request $request)
    {
        // Usamos o método centralizado da API para garantir a sessão baseada no token customizado.
        $user = $this->authenticatedUser($request);

        $data = $request->only(['title', 'description', 'equipment_id', 'room_id', 'priority']);

        // Validamos apenas os dados que entram no ticket para manter o registo consistente.
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

        // Se o ticket apontar para equipamento, confirmamos que o registo existe e está ativo.
        if (!empty($data['equipment_id'])) {
            $equipment = Equipment::find($data['equipment_id']);
            if (!$equipment || !$equipment->active) {
                return response()->json(['message' => 'Equipamento inválido ou inativo.'], 422);
            }
        }

        // O mesmo controlo aplica-se à sala associada ao ticket.
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
            // Carregamos as relações antes do envio para a notificação ter contexto completo.
            $ticket->load(['equipment', 'room', 'user']);

            // O email de criação é enviado através da mailable dedicada ao evento.
            \Illuminate\Support\Facades\Mail::to($user->email)
                ->send(new \App\Mail\TicketCreated($ticket));
        }

        event(new TicketCreatedBroadcast($ticket));

        return response()->json(['ticket' => $ticket], 201);
    }

    /**
     * Lista todos os tickets aplicando os filtros definidos.
     * - Utilizadores normais veem exclusivamente os seus próprios tickets.
     * - Técnicos e Administradores têm visibilidade global sobre os registos.
     */
    #[OA\Get(
        path: '/tickets',
        tags: ['Tickets'],
        summary: 'Listar tickets',
        security: [['X-Auth-Token' => []], ['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'priority', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'equipment_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'room_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'technician_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada de tickets')
        ]
    )]
    public function index(Request $request)
    {
        $user = $this->authenticatedUser($request);

        $query = Ticket::query()->with(['equipment', 'room', 'technician', 'user']);

        // O scope muda conforme o papel: utilizadores comuns só veem os próprios tickets.
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
            $statusName = $request->input('status');
            $statusId = Ticket::getStatusIdByName($statusName);
            if ($statusId) {
                $query->where('status_id', $statusId);
            }
        }

        // Paginação evita respostas demasiado pesadas em bases de dados maiores.
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

        // Defesa contra IDOR: um utilizador comum só pode ver os próprios tickets.
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
            User::ROLE_TECHNICIAN,
        ]);

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        if (!$ticket->hasStatus(Ticket::STATUS_OPEN)) {
            return response()->json(['message' => 'Só é possível iniciar tickets abertos'], 422);
        }

        $oldStatus          = Ticket::STATUS_OPEN;
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

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        if (!$ticket->hasStatus(Ticket::STATUS_IN_PROGRESS)) {
            return response()->json(['message' => 'Só é possível encerrar tickets em curso'], 422);
        }

        $data = $request->only(['minutes_spent', 'cost', 'report']);

        $validator = Validator::make($data, [
            'minutes_spent' => ['required', 'integer', 'min:1'],
            'cost'          => ['required', 'numeric', 'min:0'],
            'report'        => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'report.required' => 'O relatório técnico final é obrigatório.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldStatus      = Ticket::STATUS_IN_PROGRESS;
        $closedStatusId = Ticket::getStatusIdByName(Ticket::STATUS_CLOSED);
        $ticket->status_id    = $closedStatusId;
        $ticket->minutes_spent = $data['minutes_spent'];
        $ticket->cost          = $data['cost'];
        $ticket->technical_report = trim($data['report']);
        $ticket->closed_at     = now();
        $ticket->save();

        // Mantemos a mesma notificação quando o ticket é fechado.
        if ($ticket->user && $ticket->user->email) {
            $ticket->user->notify(new TicketStatusChanged($ticket, $oldStatus, Ticket::STATUS_CLOSED));
        }

        event(new TicketStatusUpdatedBroadcast($ticket, $oldStatus, Ticket::STATUS_CLOSED));

        return response()->json(['ticket' => $ticket]);
    }

    /**
     * Solicita aprovação de orçamento de reparação ao Administrador.
     */
    public function requestBudget(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            User::ROLE_TECHNICIAN,
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
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
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
            User::ROLE_TECHNICIAN,
            User::ROLE_ADMIN,
        ]);

        $query = Ticket::query()->where('scheduled', true);

        if ($request->filled('technician_id')) {
            $query->where('assigned_to', intval($request->input('technician_id')));
        }

        $tickets = $query->get();

        $events = $tickets->map(function ($t) {
            $statusName = $t->status ? $t->status->name : 'desconhecido';
            return [
                'id'            => $t->id,
                'title'         => $t->title . ' (' . $statusName . ')',
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

    /**
     * Pesquisa avançada de tickets com filtros combinados:
     * texto livre, estado, prioridade, intervalo de datas, equipamento e técnico.
     * Acessível a qualquer utilizador autenticado (com scope de visibilidade por role).
     */
    #[OA\Get(
        path: '/tickets/search',
        tags: ['Tickets'],
        summary: 'Pesquisa avançada de tickets',
        security: [['X-Auth-Token' => []], ['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'priority', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'equipment_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'technician_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Resultados da pesquisa'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function search(Request $request)
    {
        $user = $this->authenticatedUser($request);

        $validator = Validator::make($request->all(), [
            'q'              => ['nullable', 'string', 'max:255'],
            'status'         => ['nullable', 'string'],
            'priority'       => ['nullable', 'string', 'in:baixa,média,alta,crítica'],
            'equipment_id'   => ['nullable', 'integer'],
            'technician_id'  => ['nullable', 'integer'],
            'date_from'      => ['nullable', 'date'],
            'date_to'        => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'       => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = Ticket::query()->with(['equipment', 'room', 'technician', 'user', 'status']);

        // Scope por role: utilizadores comuns veem apenas os seus próprios tickets
        if ($user->isCommon()) {
            $query->where('user_id', $user->id);
        }

        // Pesquisa de texto livre no título e descrição
        if ($request->filled('q')) {
            $term = '%' . $request->input('q') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'LIKE', $term)
                  ->orWhere('description', 'LIKE', $term);
            });
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $statusId = Ticket::getStatusIdByName($request->input('status'));
            if ($statusId) {
                $query->where('status_id', $statusId);
            }
        }

        // Filtro por prioridade
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // Filtro por equipamento
        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', intval($request->input('equipment_id')));
        }

        // Filtro por técnico atribuído
        if ($request->filled('technician_id')) {
            $query->where('assigned_to', intval($request->input('technician_id')));
        }

        // Filtro por intervalo de datas de abertura
        if ($request->filled('date_from')) {
            $query->whereDate('opened_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('opened_at', '<=', $request->input('date_to'));
        }

        $perPage = $request->input('per_page', 15);
        $tickets = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'tickets' => $tickets,
            'filters' => $request->only(['q', 'status', 'priority', 'equipment_id', 'technician_id', 'date_from', 'date_to']),
        ]);
    }
}
