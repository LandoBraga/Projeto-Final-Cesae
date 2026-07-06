<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UiController;
use App\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;
// Rotas principais da API (modo simples, sem middleware explícito)
// Cada rota faz verificação de autenticação/roles dentro dos controllers.
// Padrão dos papéis:
// - Utilizador comum: criar tickets
// - Técnico: iniciar/fechar tickets e pedir orçamentos
// - ADM: gerir utilizadores/equipamentos/salas e aprovar orçamentos

Route::get('/', function () {
    return view('main');
});

Route::post('/register',                              [AuthController::class, 'register']);
Route::post('/login',                                 [AuthController::class, 'login']);
Route::post('/logout',                                [AuthController::class, 'logout']);
Route::post('/password/change',                       [AuthController::class, 'changePassword']);

Route::post('/tickets',                               [TicketController::class, 'store']);
Route::get('/tickets',                                [TicketController::class, 'index']);
Route::get('/tickets/{id}',                            [TicketController::class, 'show']);
Route::get('/technician/tickets/open',                [TicketController::class, 'openTickets']);
Route::put('/technician/tickets/{id}/start',          [TicketController::class, 'startTicket']);
Route::put('/technician/tickets/{id}/close',          [TicketController::class, 'closeTicket']);
Route::put('/technician/tickets/{id}/request-budget', [TicketController::class, 'requestBudget']);
Route::post('/tickets/{id}/assign-technician',        [TicketController::class, 'assignTechnician']);
Route::post('/tickets/{id}/reopen',                   [TicketController::class, 'reopenTicket']);
Route::post('/tickets/{id}/comments',                 [TicketController::class, 'addComment']);
Route::get('/tickets/{id}/comments',                  [TicketController::class, 'listComments']);
Route::post('/tickets/{id}/schedule',                 [TicketController::class, 'scheduleTicket']);
Route::get('/calendar/events',                        [TicketController::class, 'calendarEvents']);
Route::get('/calendar',                               [TicketController::class, 'calendarView']);

// UI routes
Route::get('/ui',                                     [UiController::class, 'index']);
Route::get('/ui/tickets',                             [UiController::class, 'tickets']);
Route::get('/ui/tickets/{id}',                         [UiController::class, 'ticketDetail']);
Route::get('/ui/equipments',                          [UiController::class, 'equipments']);
Route::get('/ui/users',                               [UiController::class, 'users']);
Route::get('/ui/audits',                              [UiController::class, 'audits']);
Route::get('/ui/login',                               [UiController::class, 'index'])->name('ui.login');

// Audit API for UI
Route::get('/admin/audits',                           [AuditController::class, 'index']);

Route::get('/admin/users',                            [AdminController::class, 'users']);
Route::patch('/admin/users/{id}/inactive',            [AdminController::class, 'inactivateUser']);

Route::get('/admin/equipment',                        [AdminController::class, 'equipments']);
Route::post('/admin/equipment',                       [AdminController::class, 'storeEquipment']);
Route::patch('/admin/equipment/{id}',                 [AdminController::class, 'updateEquipment']);
Route::delete('/admin/equipment/{id}',                [AdminController::class, 'destroyEquipment']);
Route::patch('/admin/tickets/{id}/approve-budget',    [AdminController::class, 'approveBudget']);

Route::get('/admin/rooms',                            [AdminController::class, 'rooms']);
Route::post('/admin/rooms',                           [AdminController::class, 'storeRoom']);
Route::patch('/admin/rooms/{id}',                     [AdminController::class, 'updateRoom']);
Route::patch('/admin/rooms/{id}/inactive',            [AdminController::class, 'inactivateRoom']);

Route::get('/analytics',                              [AnalyticsController::class, 'stats']);
Route::get('/analytics/export/csv',                   [AnalyticsController::class, 'exportCsv']);
Route::get('/analytics/export/pdf',                   [AnalyticsController::class, 'exportPdf']);
