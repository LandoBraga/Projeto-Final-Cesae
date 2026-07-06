<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UiController extends Controller
{
    public function index()
    {
        return view('ui.index');
    }

    public function tickets()
    {
        return view('ui.tickets');
    }

    public function equipments()
    {
        return view('ui.equipments');
    }

    public function users()
    {
        return view('ui.users');
    }

    public function audits()
    {
        return view('ui.audits');
    }

    public function ticketDetail($id)
    {
        return view('ui.ticket-detail', ['ticketId' => $id]);
    }
}
