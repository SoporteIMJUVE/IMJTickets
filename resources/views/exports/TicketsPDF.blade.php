<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>IMJ Tickets</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 10px; 
            margin: 0;
            padding: 0;
        }
        
        /*Encabezado*/
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .header-table td {
            border: none;
            vertical-align: top;
        }
        .logo {
            width: 120px;
            height: auto;
        }
        .header-text {
            text-align: right;
        }
        .header-text h2 {
            margin: 0;
            padding: 0;
            font-size: 18px;
        }
        .header-text h3 {
            margin: 5px 0;
            padding: 0;
            font-size: 14px;
            font-weight: normal;
        }
        .header-text p {
            margin: 0;
            padding: 0;
            font-size: 11px;
            color: #555;
        }

        /*Estilo de la tabla */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .data-table th, .data-table td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
            word-wrap: break-word;
        }
        .data-table th {
            background: #f2f2f2;
            text-align: center;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 20%;">
                <img src="{{ public_path('images/IMJLogo.jpeg') }}" alt="Logo" class="logo">
            </td>
            <td style="width: 80%;" class="header-text">
                <h2>Instituto Mexicano de la Juventud</h2>
                <h3>Reporte de Tickets</h3>
                <p>Generado el: {{ now()->format('d-m-Y') }}</p>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">ID</th>
                <th style="width: 10%;">Nombre</th>
                <th style="width: 14%;">Correo</th>
                <th style="width: 17%;">Descripción</th>
                <th style="width: 10%;">Tipo</th>
                <th style="width: 17%;">Área</th>
                <th style="width: 7%;">Estado</th>
                <th style="width: 7%;">Creado</th>
                <th style="width: 7%;">Atendido</th>
                <th style="width: 7%;">Cerrado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->nombre ?? '-' }}</td>
                    <td>{{ $t->correo ?? '-' }}</td>
                    <td>{{ $t->descripcion ? \Illuminate\Support\Str::limit($t->descripcion, 60, '...') : '-' }}</td>
                    <td>{{ $t->tipo ?? '-' }}</td>
                    <td>{{ $t->area ?? '-' }}</td>
                    <td>{{ \App\Models\Ticket::ESTADOS[$t->estado] ?? $t->estado }}</td>
                    <td>{{ $t->created_at ? $t->created_at->format('d-m-Y') : '-' }}</td>
                    <td>{{ $t->atendido_at ? $t->atendido_at->format('d-m-Y') : '-' }}</td>
                    <td>{{ $t->estado == 2 ? ($t->updated_at ? $t->updated_at->format('d-m-Y') : '-') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>