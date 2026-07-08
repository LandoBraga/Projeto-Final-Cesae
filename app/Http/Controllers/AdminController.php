<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Room;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Retorna todos os utilizadores (Apenas para Administradores).
     */
    public function users(Request $request)
    {
        // Adicionado eager loading para perfil para evitar N+1 queries
        return response()->json(['users' => User::with('profile')->orderBy('name')->paginate(15)]);
    }

    /**
     * Inativa um utilizador do sistema.
     */
    public function inactivateUser(Request $request, int $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilizador não encontrado'], 404);
        }

        // Impede a inativação de administradores por motivos de segurança
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Não é possível inativar um administrador'], 422);
        }

        // Marca o utilizador como inativo
        $user->active = false;
        $user->save();

        return response()->json(['message' => 'Utilizador inativado com sucesso']);
    }

    /**
     * Lista equipamentos com a respetiva sala associada.
     */
    public function equipments(Request $request)
    {
        // Adicionado paginação e ordenação
        return response()->json(['equipments' => Equipment::with('room')->orderBy('name')->paginate(15)]);
    }

    /**
     * Regista um novo equipamento no sistema.
     */
    public function storeEquipment(Request $request)
    {
        $data = $request->only(['name', 'serial', 'room_id']);
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'serial' => ['required', 'string', 'max:255', 'unique:equipments,serial'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Cria um equipamento ativo associado a uma sala (opcional)
        $equipment = Equipment::create([
            'name' => $data['name'],
            'serial' => $data['serial'],
            'room_id' => $data['room_id'] ?? null,
            'active' => true,
        ]);

        return response()->json(['equipment' => $equipment], 201);
    }

    /**
     * Atualiza os dados de um equipamento existente.
     */
    public function updateEquipment(Request $request, int $id)
    {
        $equipment = Equipment::find($id);
        if (!$equipment) {
            return response()->json(['message' => 'Equipamento não encontrado'], 404);
        }

        $data = $request->only(['name', 'serial', 'room_id', 'active']);
        $validator = Validator::make($data, [
            'name' => ['sometimes', 'string', 'max:255'],
            'serial' => ['sometimes', 'string', 'max:255', 'unique:equipments,serial,'.$id],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Garante que os campos não enviados mantêm os valores originais.
        // Evita que campos omitidos no request (mas presentes no only()) sejam gravados como null.
        $equipment->update($validator->validated());

        return response()->json(['equipment' => $equipment]);
    }

    /**
     * Remove fisicamente um equipamento do sistema.
     */
    public function destroyEquipment(Request $request, int $id)
    {
        $equipment = Equipment::find($id);
        if (!$equipment) {
            return response()->json(['message' => 'Equipamento não encontrado'], 404);
        }

        // Remove o equipamento da base de dados
        $equipment->delete();

        return response()->json(['message' => 'Equipamento eliminado']);
    }

    /**
     * Lista todas as salas registadas.
     */
    public function rooms(Request $request)
    {
        // Adicionado ordenação e paginação
        return response()->json(['rooms' => Room::orderBy('name')->paginate(15)]);
    }

    /**
     * Cria uma nova sala de trabalho.
     */
    public function storeRoom(Request $request)
    {
        $data = $request->only(['name', 'location']);
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Cria uma sala ativa
        $room = Room::create([
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'active' => true,
        ]);

        return response()->json(['room' => $room], 201);
    }

    /**
     * Atualiza os detalhes de uma sala.
     */
    public function updateRoom(Request $request, int $id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(['message' => 'Sala não encontrada'], 404);
        }

        $data = $request->only(['name', 'location']);
        $validator = Validator::make($data, [
            'name' => ['sometimes', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Garante que os campos não enviados mantêm os valores originais,
        // recorrendo apenas aos dados explicitamente validados pelo formulário/pedido.
        $room->update($validator->validated());

        return response()->json(['room' => $room]);
    }

    /**
     * Inativa uma sala (Gestão lógica / Soft management).
     */
    public function inactivateRoom(Request $request, int $id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(['message' => 'Sala não encontrada'], 404);
        }

        // Marca a sala como inativa no sistema
        $room->active = false;
        $room->save();

        return response()->json(['message' => 'Sala inativada com sucesso']);
    }

    /**
     * Aprova um pedido de orçamento associado a um ticket de avaria.
     */
    public function approveBudget(Request $request, int $id)
    {
        // Utiliza o método consistente centralizado da API (authenticatedUser)
        // para extrair o utilizador com base no cabeçalho X-Auth-Token.
        $admin = $this->authenticatedUser($request);

        // Opcional: Garante programaticamente que o utilizador autenticado possui o papel de Administrador
        $this->requireRole($admin, [User::ROLE_ADMIN]);

        // Procura o ticket que tem um pedido de orçamento pendente
        $ticket = Ticket::find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        // Verifica se existe um pedido de orçamento e se o mesmo se encontra pendente
        if (!$ticket->budget_requested || $ticket->budget_status !== Ticket::BUDGET_PENDING) {
            return response()->json(['message' => 'Não existe pedido de orçamento pendente'], 422);
        }

        // Executa a aprovação do orçamento através do método do modelo, registando o autor
        $approved = $ticket->approveBudget($admin);

        if (!$approved) {
            return response()->json(['message' => 'Aprovação falhou'], 422);
        }

        // Retorna o ticket devidamente atualizado
        return response()->json(['ticket' => $ticket]);
    }
}
