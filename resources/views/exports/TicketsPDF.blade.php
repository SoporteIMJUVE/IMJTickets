<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>IMJ Tickets</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; word-wrap: break-word; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h3>Tickets — {{ now()->format('d-m-Y H:i') }}</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Área</th>
                <th>Estado</th>
                <th>Creado</th>
                <th>Atendido</th>
                <th>Cerrado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->nombre ?? '-' }}</td>
                    <td>{{ $t->correo ?? '-' }}</td>
                    <td>{{ $t->descripcion ?? '-' }}</td>
                    <td>{{ $t->tipo ?? '-' }}</td>
                    <td>{{ $t->area ?? '-' }}</td>
                    <td>{{ \App\Models\Ticket::ESTADOS[$t->estado] ?? $t->estado }}</td>
                    <td>{{ $t->created_at }}</td>
                    <td>{{ $t->atendido_at ?? '-' }}</td>
                    <td>{{ $t->estado == 2 ? ($t->updated_at ?? '-') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>