<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
// Rotas principais da API (modo simples, sem middleware explícito)
// Cada rota faz verificação de autenticação/roles dentro dos controllers.
// Padrão dos papéis:
// - Utilizador comum: criar tickets
// - Técnico: iniciar/fechar tickets e pedir orçamentos
// - ADM: gerir utilizadores/equipamentos/salas e aprovar orçamentos

Route::get('/', function () {
    return response()->json([
        'message' => 'Sistema de gestão de avarias',
        'routes' => [
            'POST /register',
            'POST /login',
            'POST /logout',
            'POST /password/change',
            'POST /tickets',
            'GET /tickets',
            'GET /technician/tickets/open',
            'PUT /technician/tickets/{id}/start',
            'PUT /technician/tickets/{id}/close',
            'PUT /technician/tickets/{id}/request-budget',
            'PATCH /admin/tickets/{id}/approve-budget',
            'GET /admin/users',
            'PATCH /admin/users/{id}/inactive',
            'GET /admin/equipment',
            'POST /admin/equipment',
            'PATCH /admin/equipment/{id}',
            'DELETE /admin/equipment/{id}',
            'GET /admin/rooms',
            'POST /admin/rooms',
            'PATCH /admin/rooms/{id}',
            'PATCH /admin/rooms/{id}/inactive',
            'GET /analytics',
        ],
    ]);
});

Route::post('/register',                     [AuthController::class, 'register']);
Route::post('/login',                        [AuthController::class, 'login']);
Route::post('/logout',                       [AuthController::class, 'logout']);
Route::post('/password/change',              [AuthController::class, 'changePassword']);

Route::post('/tickets',                      [TicketController::class, 'store']);
Route::get('/tickets',                       [TicketController::class, 'index']);
Route::get('/technician/tickets/open',       [TicketController::class, 'openTickets']);
Route::put('/technician/tickets/{id}/start', [TicketController::class, 'startTicket']);
Route::put('/technician/tickets/{id}/close', [TicketController::class, 'closeTicket']);
Route::put('/technician/tickets/{id}/request-budget', [TicketController::class, 'requestBudget']);

Route::get('/admin/users',                   [AdminController::class, 'users']);
Route::patch('/admin/users/{id}/inactive',   [AdminController::class, 'inactivateUser']);

Route::get('/admin/equipment',               [AdminController::class, 'equipments']);
Route::post('/admin/equipment',              [AdminController::class, 'storeEquipment']);
Route::patch('/admin/equipment/{id}',        [AdminController::class, 'updateEquipment']);
Route::delete('/admin/equipment/{id}',       [AdminController::class, 'destroyEquipment']);
Route::patch('/admin/tickets/{id}/approve-budget', [AdminController::class, 'approveBudget']);

Route::get('/admin/rooms',                   [AdminController::class, 'rooms']);
Route::post('/admin/rooms',                  [AdminController::class, 'storeRoom']);
Route::patch('/admin/rooms/{id}',            [AdminController::class, 'updateRoom']);
Route::patch('/admin/rooms/{id}/inactive',   [AdminController::class, 'inactivateRoom']);

Route::get('/analytics',                     [AnalyticsController::class, 'stats']);
