<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>{{ $titulo ?? 'Reporte de tickets IMJUVE' }}</title>
        <style>
            body { 
                font-family: DejaVu Sans, sans-serif; 
                font-size: 10px; 
                margin: 0;
                padding: 0;
            }

            /* Paginación */
            #footer {
                position: fixed;
                left: 0px;
                bottom: -50px;
                right: 0px;
                height: 50px;
                text-align: right;
                font-size: 10px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 5px;
            }

            #footer .page-number:after {
                content: "Página " counter(page);
            }
            
            /*Encabezado*/
            .header-table {
                width: 100%;
                border-collapse: collapse;
                border-bottom: 2px solid #681a32;
                padding-bottom: 10px;
                margin-bottom: 20px;
                font-family: sans-serif;
            }
            .logo-cell {
                width: 20%;
                vertical-align: middle;
            }
            .logo {
                max-width: 220px;
                height: auto;
            }
            .text-cell {
                width: 80%;
                text-align: right;
                vertical-align: middle; 
                color: #333;
            }       
            .title-main {
                font-size: 18px;
                font-weight: bold;
                text-transform: uppercase;
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
                color: #681a32;
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
                word-wrap: break-word;
            }
            .data-table th {
                background: #f2f2f2;
                text-align: center;
            }

            /* Alineación */
            .text-center { 
                text-align: center; 
            }
            .text-justify { 
                text-align: justify; 
                line-height: 1.2;
            }
        </style>
    </head>
    <body>
        <div id="footer">
            <span class="page-number"></span>
        </div>
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img src="{{ public_path('images/IMJLogo.jpeg') }}" alt="Logo" class="logo">
                </td>
                <td class="text-cell">
                    <h1 class="title-main">Instituto Mexicano de la Juventud</h1>
                    <h2 class="title-sub">Subdirección de Sistemas</h2>
                    <h3 class="report-name">{{ $titulo ?? 'Reporte de Tickets' }}</h3>
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
                    <th style="width: 12%;">Nombre</th>
                    <th style="width: 12%;">Correo</th>
                    <th style="width: 18%;">Descripción</th>
                    <th style="width: 18%;">Comentarios</th>
                    <th style="width: 12%;">Tipo</th>
                    <th style="width: 12%;">Área</th>
                    <th style="width: 8%;">Estado</th>
                    <th style="width: 8%;">Creado</th>
                    <th style="width: 8%;">Cerrado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tickets as $t)
                    <tr>
                        <td class="text-center">{{ $t->id }}</td>
                        <td class="text-center">{{ $t->nombre ?? '-' }}</td>
                        <td class="text-center">{{ $t->correo ?? '-' }}</td>
                        <td class="text-justify">{{ $t->descripcion ?? '-' }}</td>
                        <td class="text-justify">{{ $t->comentarios ?? '-' }}</td>
                        <td class="text-center">{{ $t->tipo ?? '-' }}</td>
                        <td class="text-center">{{ $t->area ?? '-' }}</td>
                        <td class="text-center">{{ \App\Models\Ticket::ESTADOS[$t->estado] ?? $t->estado }}</td>
                        <td class="text-center">{{ $t->created_at ? $t->created_at->format('d-m-Y') : '-' }}</td>
                        <td class="text-center">{{ $t->cerrado_at ? $t->cerrado_at->format('d-m-Y') : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>