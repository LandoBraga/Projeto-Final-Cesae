<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tickets Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Relatório de Tickets</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Estado</th>
                <th>Abertura</th>
                <th>Em Curso</th>
                <th>Fecho</th>
                <th>Minutos</th>
                <th>Custo</th>
                <th>Orçamento - Estado</th>
                <th>Orçamento - Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->title }}</td>
                    <td>{{ $t->status }}</td>
                    <td>{{ optional($t->opened_at)->toDateTimeString() }}</td>
                    <td>{{ optional($t->in_progress_at)->toDateTimeString() }}</td>
                    <td>{{ optional($t->closed_at)->toDateTimeString() }}</td>
                    <td>{{ $t->minutes_spent }}</td>
                    <td>{{ $t->cost }}</td>
                    <td>{{ $t->budget_status }}</td>
                    <td>{{ $t->budget_amount }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
