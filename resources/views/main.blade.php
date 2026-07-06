<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestão de Avarias - Página Principal</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; margin: 32px; line-height: 1.6; }
        .card { border: 1px solid #d0d7de; border-radius: 8px; padding: 20px; max-width: 780px; }
        .grid { display: grid; gap: 10px; margin-top: 16px; }
        a { color: #0a66c2; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .muted { color: #57606a; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Página principal</h1>
        <p class="muted">Escolha uma área para abrir o respetivo painel.</p>
        <div class="grid">
            <div><a href="/ui">Dashboard</a></div>
            <div><a href="/ui/tickets">Tickets</a></div>
            <div><a href="/ui/equipments">Equipamentos</a></div>
            <div><a href="/ui/users">Utilizadores</a></div>
            <div><a href="/ui/audits">Auditoria</a></div>
            <div><a href="/calendar">Agenda</a></div>
            <div><a href="/ui/login">Login</a></div>
        </div>
    </div>
</body>
</html>
