<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UiController extends Controller
{
    /**
     * Mostra o painel principal da interface web.
     */
    public function index()
    {
        return view('ui.index');
    }

    /**
     * Mostra a página com a lista de tickets.
     */
    public function tickets()
    {
        return view('ui.tickets');
    }

    /**
     * Mostra a página com os equipamentos registados.
     */
    public function equipments()
    {
        return view('ui.equipments');
    }

    /**
     * Mostra a página com os utilizadores do sistema.
     */
    public function users()
    {
        return view('ui.users');
    }

    /**
     * Mostra a página de auditoria.
     */
    public function audits()
    {
        return view('ui.audits');
    }

    /**
     * Mostra os detalhes de um ticket específico.
     */
    public function ticketDetail($id)
    {
        return view('ui.ticket-detail', ['ticketId' => $id]);
    }
}
