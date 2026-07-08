<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApiDocsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UiController;
use App\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Públicas (Acesso Aberto)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('main');
});

Route::get('/ui/login', function () {
    return view('ui.auth');
})->name('ui.login');

// Documentação da API
Route::get('/docs/openapi', [ApiDocsController::class, 'swagger']);

// Endpoints Públicos de Autenticação (Guest) - com rate limiting
Route::post('/register', [AuthController::class, 'register'])->middleware(['rate.limit:5,1']);
Route::post('/login',    [AuthController::class, 'login'])->middleware(['rate.limit:5,1']);


/*
|--------------------------------------------------------------------------
| Rotas Protegidas (Exigem Token de Autenticação Válido)
|--------------------------------------------------------------------------
*/
Route::middleware(['custom.auth'])->group(function () {

    // Ações de conta comuns a qualquer utilizador logado
    Route::post('/logout',           [AuthController::class, 'logout']);
    Route::post('/password/change',  [AuthController::class, 'changePassword']);

    // ========================================
    // Rotas de Interface Web (UI) Protegidas
    // ========================================

    // UI - Acesso para todos os utilizadores autenticados
    Route::middleware([])->group(function () {
        Route::get('/ui',            [UiController::class, 'index']);
        Route::get('/ui/tickets',    [UiController::class, 'tickets']);
        Route::get('/ui/tickets/{id}', [UiController::class, 'ticketDetail']);
        Route::get('/ui/equipments', [UiController::class, 'equipments']);
        Route::get('/equipments',    [UiController::class, 'getEquipments']);
    });

    // UI - Acesso para Técnicos e Administradores
    Route::middleware(['role:technician,admin'])->group(function () {
        Route::get('/ui/users',      [UiController::class, 'users']);
        Route::get('/ui/audits',     [UiController::class, 'audits']);
    });

    // Consultas gerais e interações nos tickets (Policiamento fino feito por ID nos métodos)
    Route::get('/tickets',              [TicketController::class, 'index']);
    Route::get('/tickets/{id}',         [TicketController::class, 'show']);
    Route::post('/tickets/{id}/comments', [TicketController::class, 'addComment']);
    Route::get('/tickets/{id}/comments',  [TicketController::class, 'listComments']);
    Route::post('/tickets/{id}/photos',   [TicketController::class, 'uploadPhoto']);
    Route::get('/tickets/{id}/photos',    [TicketController::class, 'listPhotos']);

    // Rotas de Fluxo Misto/Avançado
    Route::post('/tickets/{id}/reopen',   [TicketController::class, 'reopenTicket']);
    Route::post('/tickets/{id}/schedule', [TicketController::class, 'scheduleTicket']);

    /*
     |-- Área do Funcionário / Operário Comum
     |----------------------------------------------------------------------*/
    Route::middleware(['role:user'])->group(function () {
        Route::post('/tickets', [TicketController::class, 'store']);
    });

    /*
     |-- Área Exclusiva do Técnico de Manutenção
     |----------------------------------------------------------------------*/
    Route::middleware(['role:technician'])->group(function () {
        Route::put('/technician/tickets/{id}/start',          [TicketController::class, 'startTicket']);
        Route::put('/technician/tickets/{id}/close',          [TicketController::class, 'closeTicket']);
        Route::put('/technician/tickets/{id}/request-budget', [TicketController::class, 'requestBudget']);
    });

    /*
     |-- Área Partilhada (Técnicos e Administradores)
     |----------------------------------------------------------------------*/
    Route::middleware(['role:technician,admin'])->group(function () {
        Route::get('/technician/tickets/open',      [TicketController::class, 'openTickets']);
        Route::post('/tickets/{id}/assign-technician', [TicketController::class, 'assignTechnician']);

        // Calendário Operacional
        Route::get('/calendar/events', [TicketController::class, 'calendarEvents']);
        Route::get('/calendar',        [TicketController::class, 'calendarView']);

        // Módulo Analítico e Relatórios
        Route::get('/analytics',            [AnalyticsController::class, 'stats']);
        Route::get('/analytics/export/csv', [AnalyticsController::class, 'exportCsv']);
        Route::get('/analytics/export/pdf', [AnalyticsController::class, 'exportPdf']);
    });

    /*
     |-- Área de Administração e Backoffice (Direção de Operações)
     |----------------------------------------------------------------------*/
    Route::middleware(['role:admin'])->group(function () {
        // Logs de Auditoria do Sistema
        Route::get('/admin/audits', [AuditController::class, 'index']);

        // Gestão de Utilizadores (CRUD / Estado)
        Route::get('/admin/users',               [AdminController::class, 'users']);
        Route::patch('/admin/users/{id}/inactive', [AdminController::class, 'inactivateUser']);

        // Gestão do Inventário de Equipamentos
        Route::get('/admin/equipment',        [AdminController::class, 'equipments']);
        Route::post('/admin/equipment',       [AdminController::class, 'storeEquipment']);
        Route::patch('/admin/equipment/{id}',  [AdminController::class, 'updateEquipment']);
        Route::delete('/admin/equipment/{id}', [AdminController::class, 'destroyEquipment']);

        // Decisão Orçamental de Engenharia
        Route::patch('/admin/tickets/{id}/approve-budget', [AdminController::class, 'approveBudget']);

        // Gestão de Infraestrutura (Salas / Pavilhões)
        Route::get('/admin/rooms',               [AdminController::class, 'rooms']);
        Route::post('/admin/rooms',              [AdminController::class, 'storeRoom']);
        Route::patch('/admin/rooms/{id}',        [AdminController::class, 'updateRoom']);
        Route::patch('/admin/rooms/{id}/inactive', [AdminController::class, 'inactivateRoom']);
    });
});
