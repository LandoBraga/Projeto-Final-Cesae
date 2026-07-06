<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Room;
use App\Models\Ticket;
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

        $data = $request->only(['title', 'description', 'equipment_id', 'room_id']);

        // Validação dos campos recebidos pelo request
        $validator = Validator::make($data, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'equipment_id' => ['nullable', 'integer', 'exists:equipments,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
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

        if ($user->isCommon()) {
            $tickets = Ticket::where('user_id', $user->id)->get();
        } else {
            $tickets = Ticket::all();
        }

        // Retorna lista de tickets conforme permissões
        return response()->json(['tickets' => $tickets]);
    }

    /**
     * Lista tickets com estado `aberta`.
     * - Disponível para Técnicos e ADM.
     */
    public function openTickets(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [
            $user::ROLE_TECHNICIAN,
            $user::ROLE_ADMIN,
        ]);

        $tickets = Ticket::where('status', Ticket::STATUS_OPEN)->get();

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

        // Procura o ticket pelo id
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
}
