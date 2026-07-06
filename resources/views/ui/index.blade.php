@extends('ui.layout')

@section('content')
<h1>Painel - Gestão de Avarias</h1>
<p>Escolha uma secção para continuar.</p>
<ul>
    <li><a href="/ui/tickets">Ver Tickets</a></li>
    <li><a href="/ui/equipments">Ver Equipamentos</a></li>
    <li><a href="/ui/users">Ver Utilizadores</a></li>
    <li><a href="/ui/audits">Ver Auditoria</a></li>
    <li><a href="/calendar">Abrir Agenda</a></li>
</ul>
<p><a href="/">Voltar para a página principal</a></p>
@endsection
