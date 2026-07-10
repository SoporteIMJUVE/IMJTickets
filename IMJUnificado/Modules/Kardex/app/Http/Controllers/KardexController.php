<?php

namespace Modules\Kardex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class KardexController extends Controller
{
    // ─── Vista: formulario de carga ──────────────────────────────────────────

    public function subirResguardo()
    {
        return view('kardex::resguardo-subir');
    }

    // ─── POST: extrae texto del PDF y muestra previsualización ───────────────

    public function extraerResguardo(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:10240',
        ], [
            'pdf.required' => 'Selecciona un archivo PDF.',
            'pdf.mimes'    => 'El archivo debe ser un PDF.',
            'pdf.max'      => 'El archivo no debe superar 10 MB.',
        ]);

        $file = $request->file('pdf');

        $parser = new Parser();
        $pdf    = $parser->parseFile($file->getPathname());
        $texto  = $pdf->getText();

        $esNativo = mb_strlen(trim($texto)) > 80;

        // Si tiene texto: extraer campos
        // Si es imagen (sin texto): datos vacíos, formulario manual
        $datos = $esNativo
            ? $this->extraerCampos($texto)
            : array_fill_keys(['nombre_usuario','tipo','cpu_marca','cpu_modelo','cpu_serie',
                               'consecutivo','num_inventario','area','observaciones'], null);

        $hayDatos = $esNativo && count(array_filter($datos)) > 0;

        // Solo buscar candidatos si extrajimos un nombre
        $candidatos = collect();
        if ($hayDatos && $datos['nombre_usuario']) {
            $partes   = preg_split('/\s+/', trim($datos['nombre_usuario']));
            $nombre   = $partes[0] ?? '';
            $apellido = $partes[1] ?? '';
            $candidatos = DB::table('empleados')
                ->leftJoin('departamentos', 'empleados.id_departamento', '=', 'departamentos.id_departamento')
                ->where('empleados.activo', 1)
                ->where(function ($q) use ($nombre, $apellido) {
                    $q->where('empleados.nombre', 'like', "%{$nombre}%")
                      ->orWhere('empleados.apellido_paterno', 'like', "%{$apellido}%")
                      ->orWhere('empleados.apellido_materno', 'like', "%{$apellido}%");
                })
                ->select(
                    'empleados.id_empleado',
                    'empleados.nombre',
                    'empleados.apellido_paterno',
                    'empleados.apellido_materno',
                    'empleados.correo',
                    'departamentos.nombre as departamento'
                )
                ->limit(5)
                ->get();
        }

        // Siempre guardar el PDF (también para escaneados)
        $tmpPath = $file->store('resguardos_tmp', 'local');

        session([
            'resguardo.datos'      => $datos,
            'resguardo.textoRaw'   => $texto,
            'resguardo.candidatos' => $candidatos,
            'resguardo.tmpPdf'     => $tmpPath,
            'resguardo.esNativo'   => $esNativo,
        ]);

        return redirect()->route('kardex.resguardo.preview');
    }

    // ─── GET: muestra previsualización (PRG) ─────────────────────────────────

    public function mostrarPreview()
    {
        if (!session()->has('resguardo.datos')) {
            return redirect()->route('kardex.resguardo.subir')
                ->withErrors(['pdf' => 'La sesión expiró. Vuelve a subir el PDF.']);
        }

        return view('kardex::resguardo-preview', [
            'datos'      => session('resguardo.datos'),
            'textoRaw'   => session('resguardo.textoRaw'),
            'candidatos' => collect(session('resguardo.candidatos', []))->map(fn($e) => (object) $e),
            'tmpPdf'     => session('resguardo.tmpPdf'),
            'esNativo'   => session('resguardo.esNativo', true),
        ]);
    }

    // ─── POST: confirma y guarda en base de datos ─────────────────────────────

    public function guardarResguardo(Request $request)
    {
        $request->validate([
            'tipo'          => 'required|in:Laptop,PC Avanzada,PC Especializada',
            'cpu_serie'     => 'nullable|string|max:100',
            'tmp_pdf'       => 'required|string',
            'id_empleado'   => 'nullable|exists:empleados,id_empleado',
            'ipv4'          => 'nullable|ip',
        ], [
            'tipo.required' => 'El tipo de equipo es obligatorio.',
        ]);

        // Verificar serie duplicada
        $existente = DB::table('inventario_equipos')
            ->where('cpu_serie', $request->cpu_serie)
            ->first();

        if ($existente) {
            return redirect()->route('kardex.resguardo.preview')->withInput()->withErrors([
                'cpu_serie' => "Ya existe un equipo con la serie {$request->cpu_serie} (ID: {$existente->id}).",
            ]);
        }

        $idEquipo = DB::table('inventario_equipos')->insertGetId([
            'tipo'           => $request->tipo,
            'consecutivo'    => $request->consecutivo ?: null,
            'num_inventario' => $request->num_inventario ?: null,
            'nombre_equipo'  => trim($request->cpu_marca . ' ' . $request->cpu_modelo) ?: null,
            'nombre_usuario' => $request->nombre_usuario ?: null,
            'area'           => $request->area ?: null,
            'cpu_marca'      => $request->cpu_marca ?: null,
            'cpu_modelo'     => $request->cpu_modelo ?: null,
            'cpu_serie'      => $request->cpu_serie,
            'cargador_serie' => $request->cargador_serie ?: null,
            'docking_marca'  => $request->docking_marca ?: null,
            'docking_modelo' => $request->docking_modelo ?: null,
            'docking_serie'  => $request->docking_serie ?: null,
            'monitor_marca'  => $request->monitor_marca ?: null,
            'monitor_modelo' => $request->monitor_modelo ?: null,
            'monitor_serie'  => $request->monitor_serie ?: null,
            'teclado_serie'  => $request->teclado_serie ?: null,
            'mouse_serie'    => $request->mouse_serie ?: null,
            'nobreak_marca'  => $request->nobreak_marca ?: null,
            'nobreak_modelo' => $request->nobreak_modelo ?: null,
            'nobreak_serie'  => $request->nobreak_serie ?: null,
            'ipv4'           => $request->ipv4 ?: null,
            'observaciones'  => $request->observaciones ?: null,
            'id_empleado'    => $request->id_empleado ?: null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Mover PDF de tmp a almacenamiento permanente y registrar path
        $pdfPath = null;
        if ($request->tmp_pdf && Storage::disk('local')->exists($request->tmp_pdf)) {
            $pdfPath = "resguardos/{$idEquipo}.pdf";
            Storage::disk('local')->move($request->tmp_pdf, $pdfPath);
            DB::table('inventario_equipos')->where('id', $idEquipo)->update(['pdf_resguardo' => $pdfPath]);
        }

        return redirect()->route('kardex.index')
            ->with('success', "Equipo registrado (ID {$idEquipo}). PDF guardado en el sistema.");
    }

    // ─── Privado: extrae campos del texto del PDF ─────────────────────────────

    private function extraerCampos(string $texto): array
    {
        $find = function (string $pattern) use ($texto): ?string {
            return preg_match($pattern, $texto, $m) ? trim($m[1]) : null;
        };

        // Nombre del usuario — "NOMBRE DEL USUARIO: ..."
        $nombreUsuario = $find('/NOMBRE DEL USUARIO\s*[:\|]?\s*(.+)/i');

        // Tipo de equipo
        $tipo = $find('/(Laptop|PC Avanzada|PC Especializada)/i');
        if ($tipo) {
            $mapa = ['laptop' => 'Laptop', 'pc avanzada' => 'PC Avanzada', 'pc especializada' => 'PC Especializada'];
            $tipo = $mapa[strtolower($tipo)] ?? $tipo;
        }

        // Número de serie — busca patrones alfanuméricos de ≥5 chars cerca de "SERIE"
        $serie = $find('/NO[\.\s]*SERIE\s*[:\|]?\s*([A-Z0-9\-]{5,})/i');

        // Número de inventario
        $inventario = $find('/INVENTARIO\s*[:\|]?\s*(\d+)/i');

        // Marca y modelo — aparecen en la fila de la tabla de equipo
        $marca  = null;
        $modelo = null;
        if ($tipo) {
            $tipoEsc = preg_quote($tipo, '/');
            if (preg_match('/' . $tipoEsc . '\s+([\w]+)\s+([\w][\w\s\-]+?)(?:\s{2,}|\t)/i', $texto, $m)) {
                $marca  = trim($m[1]);
                $modelo = trim($m[2]);
            }
        }

        // Área — "ÁREA: ..."
        $area = $find('/ÁREA\s*[:\|]?\s*(.+)/i');

        // Observaciones — texto después del encabezado OBSERVACIONES
        $observaciones = null;
        if (preg_match('/OBSERVACIONES\s*\n+(.+?)(?:\n{2,}|¿El equipo|$)/si', $texto, $m)) {
            $observaciones = trim($m[1]);
        }

        return [
            'nombre_usuario' => $nombreUsuario,
            'tipo'           => $tipo,
            'cpu_marca'      => $marca,
            'cpu_modelo'     => $modelo,
            'cpu_serie'      => $serie,
            'consecutivo'    => $inventario ? (int) $inventario : null,
            'num_inventario' => $inventario,
            'area'           => $area,
            'observaciones'  => $observaciones,
        ];
    }

    // ─── Público: sugiere primera IP libre del rango del área ────────────────

    public function sugerirIp(Request $request)
    {
        $area = $request->query('area');
        if (!$area) return response()->json(['ip' => null]);

        $rango = DB::table('cat_rangos_ips')
            ->where('area_nombre', 'like', "%{$area}%")
            ->first();

        if (!$rango) return response()->json(['ip' => null, 'mensaje' => 'Sin rango para esa área']);

        $usadas = DB::table('inventario_equipos')
            ->whereNotNull('ipv4')
            ->pluck('ipv4')
            ->flip()
            ->all();

        $inicio = ip2long($rango->ip_inicial);
        $fin    = ip2long($rango->ip_final);

        for ($n = $inicio; $n <= $fin; $n++) {
            $candidata = long2ip($n);
            if (!isset($usadas[$candidata])) {
                return response()->json(['ip' => $candidata]);
            }
        }

        return response()->json(['ip' => null, 'mensaje' => 'Rango sin IPs disponibles']);
    }
}
