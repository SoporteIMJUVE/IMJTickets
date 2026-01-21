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
            border-collapse: collapse; /* Elimina espacios entre celdas */
            border-bottom: 2px solid #9D2449; /* Color institucional aprox. (Guinda) */
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-family: sans-serif;
        }
        .logo-cell {
            width: 20%;
            vertical-align: middle; /* Centra el logo verticalmente */
        }
        .logo {
            max-width: 160px; /* Controla el tamaño para que no se pixel */
            height: auto;
        }
        .text-cell {
            width: 80%;
            text-align: right;
            vertical-align: middle; /* Centra el texto respecto al logo */
            color: #333;
        }       
        .title-main {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase; /* Más formal */
            margin: 0;
            color: #2c2c2c;
        }
        .title-sub {
            font-size: 18px;
            margin: 2px 0;
            font-weight: bold;
        }
        .report-name {
            font-size: 16px;
            font-weight: noprmal;
            color: #9D2449; /* Destaca el nombre del reporte */
            margin-top: 5px;
            margin-bottom: 0;
        }
        .meta-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
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
        <td class="logo-cell">
            <img src="{{ public_path('images/IMJLogo.jpeg') }}" alt="Logo" class="logo">
        </td>
        <td class="text-cell">
            <h1 class="title-main">Instituto Mexicano de la Juventud</h1>
            <h2 class="title-sub">Subdirección de Sistemas</h2>
            <h3 class="report-name">Reporte de Tickets</h3>
            <p class="meta-info">
                Generado el: {{ now()->format('d/m/Y') }} &bull; Hora: {{ now()->format('H:i') }}
            </p>
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