<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Controller de administração. Métodos aqui exigem que o utilizador
     * autenticado seja um ADM (`ROLE_ADMIN`).
     *
     * Fornece operações para gerir utilizadores, equipamentos, salas e
     * aprovar pedidos de orçamento solicitados pelos técnicos.
     */
    public function users(Request $request)
    {
        $admin = $this->authenticatedUser($request);
        $this->requireRole($admin, [User::ROLE_ADMIN]);

        // Retorna todos os utilizadores (APENAS para ADM)
        return response()->json(['users' => User::all()]);
    }

    public function inactivateUser(Request $request, int $id)
    {
        $admin = $this->authenticatedUser($request);
        $this->requireRole($admin, [User::ROLE_ADMIN]);

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilizador não encontrado'], 404);
        }

        // Impede inativação de administradores por segurança
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Não é possível inativar um administrador'], 422);
        }

        // Marca o utilizador como inactivo
        $user->active = false;
        $user->save();

        return response()->json(['message' => 'Utilizador inativado com sucesso']);
    }

    public function equipments(Request $request)
    {
        $admin = $this->authenticatedUser($request);
        $this->requireRole($admin, [User::ROLE_ADMIN]);

        // Lista equipamentos com a sala associada
        return response()->json(['equipments' => Equipment::with('room')->get()]);
    }

    public function storeEquipment(Request $request)
    {
        $admin = $this->authenticatedUser($request);
        $this->requireRole($admin, [User::ROLE_ADMIN]);

        $data = $request->only(['name', 'serial', 'room_id']);
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'serial' => ['required', 'string', 'max:255', 'unique:equipments,serial'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Cria um equipamento activo associado a uma sala (opcional)
        $equipment = Equipment::create([
            'name' => $data['name'],
            'serial' => $data['serial'],
            'room_id' => $data['room_id'] ?? null,
            'active' => true,
        ]);

        return response()->json(['equipment' => $equipment], 201);
    }

    public function updateEquipment(Request $request, int $id)
    {
        $admin = $this->authenticatedUser($request);
        $this->requireRole($admin, [User::ROLE_ADMIN]);

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

        // Actualiza os campos fornecidos no equipamento
        $equipment->update($data);

        return response()->json(['equipment' => $equipment]);
    }

    public function destroyEquipment(Request $request, int $id)
    {
        $admin = $this->authenticatedUser($request);
        $this->requireRole($admin, [User::ROLE_ADMIN]);

        $equipment = Equipment::find($id);
        if (!$equipment) {
            return response()->json(['message' => 'Equipamento não encontrado'], 404);
        }

        // Remove o equipamento da base de dados
        $equipment->delete();

        return response()->json(['message' => 'Equipamento eliminado']);
    }

    public function rooms(Request $request)
    {
        $admin = $this->authenticatedUser($request);
        $this->requireRole($admin, [User::ROLE_ADMIN]);

        // Lista todas as salas (ADM)
        return response()->json(['rooms' => Room::all()]);
    }

    public function storeRoom(Request $request)
    {
        $admin = $this->authenticatedUser($request);
        $this->requireRole($admin, [User::ROLE_ADMIN]);

        $data = $request->only(['name', 'location']);
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Cria uma sala activa
        $room = Room::create([
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'active' => true,
        ]);

        return response()->json(['room' => $room], 201);
    }

    public function updateRoom(Request $request, int $id)
    {
        $admin = $this->authenticatedUser($request);
        $this->requireRole($admin, [User::ROLE_ADMIN]);

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

        // Actualiza os dados da sala
        $room->update($data);

        return response()->json(['room' => $room]);
    }

    public function inactivateRoom(Request $request, int $id)
    {
        $admin = $this->authenticatedUser($request);
        $this->requireRole($admin, [User::ROLE_ADMIN]);

        $room = Room::find($id);
        if (!$room) {
            return response()->json(['message' => 'Sala não encontrada'], 404);
        }

        // Marca a sala como inactiva
        $room->active = false;
        $room->save();

        return response()->json(['message' => 'Sala inativada com sucesso']);
    }

    public function approveBudget(Request $request, int $id)
    {
        $admin = $this->authenticatedUser($request);
        $this->requireRole($admin, [User::ROLE_ADMIN]);

        // Procura o ticket que tem um pedido de orçamento pendente
        $ticket = \App\Models\Ticket::find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado'], 404);
        }

        // Verifica se existe um pedido e se está pendente
        if (! $ticket->budget_requested || $ticket->budget_status !== \App\Models\Ticket::BUDGET_PENDING) {
            return response()->json(['message' => 'Não existe pedido de orçamento pendente'], 422);
        }

        // Executa a aprovação (método do modelo) que grava `budget_approved_by`
        $approved = $ticket->approveBudget($admin);

        if (! $approved) {
            return response()->json(['message' => 'Aprovação falhou'], 422);
        }

        // Retorna ticket actualizado
        return response()->json(['ticket' => $ticket]);
    }
}
