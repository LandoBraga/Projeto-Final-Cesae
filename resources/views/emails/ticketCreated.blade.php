<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px; max-width: 600px; margin: auto; background-color: #f9f9f9; }
        .header { background-color: #007bff; color: white; padding: 15px; border-radius: 5px 5px 0 0; text-align: center; }
        .details { margin: 20px 0; background: white; padding: 15px; border-radius: 5px; }
        .details p { margin: 8px 0; }
        .footer { font-size: 0.85em; color: #777; text-align: center; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Nova Avaria Registada #{{ $ticket->id }}</h2>
        </div>

        <p>Olá,</p>
        <p>Um novo ticket foi submetido no sistema. Abaixo seguem os detalhes:</p>

        <div class="details">
            <p><strong>Título:</strong> {{ $ticket->title }}</p>
            <p><strong>Equipamento:</strong> {{ $ticket->equipment ? $ticket->equipment->name : 'Não especificado' }}</p>
            <p><strong>Sala:</strong> {{ $ticket->room ? $ticket->room->name : 'Não especificada' }}</p>
            <p><strong>Prioridade:</strong> {{ ucfirst($ticket->priority) }}</p>
            <p><strong>Descrição:</strong> {{ $ticket->description }}</p>
            <p><strong>Registado por:</strong> {{ $ticket->user ? $ticket->user->name : 'Utilizador' }}</p>
            <p><strong>Data:</strong> {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <p>Pode consultar o estado do ticket diretamente no painel administrativo.</p>

        <div class="footer">
            <p>Este é um email automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>
