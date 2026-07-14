<?php

namespace Modules\Tickets\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Tickets\Models\Ticket;

class TicketsController extends Controller
{
    public function index()
    {
        $tickets = DB::table('tickets')->orderByDesc('created_at')->get();

        $porEstado = [
            0 => $tickets->where('estado', 0)->values(),
            1 => $tickets->where('estado', 1)->values(),
            2 => $tickets->where('estado', 2)->values(),
        ];

        $tecnicos = DB::table('users')->select('name', 'email')->get();
        $areas    = DB::table('areas')->orderBy('nombre')->pluck('nombre');

        $comentariosPorTicket = Schema::hasTable('ticket_comentarios')
            ? DB::table('ticket_comentarios')->orderBy('created_at')->get()
                ->groupBy('ticket_id')
                ->map(fn($g) => $g->map(fn($c) => [
                    'id'           => $c->id,
                    'autor_nombre' => $c->autor_nombre,
                    'autor_email'  => $c->autor_email,
                    'texto'        => $c->texto,
                    'created_at'   => $c->created_at,
                ])->values()->all())
                ->all()
            : [];

        $hace7 = now()->subDays(7)->toDateTimeString();

        // Promedio de resolución (tickets cerrados en los últimos 7 días)
        $cerrados7 = DB::table('tickets')
            ->whereNotNull('cerrado_at')
            ->where('cerrado_at', '>=', $hace7)
            ->selectRaw("AVG((julianday(cerrado_at) - julianday(created_at)) * 24) as avg_horas")
            ->value('avg_horas');
        $promedioResolucion = $cerrados7
            ? ($cerrados7 < 1
                ? round($cerrados7 * 60) . ' min'
                : round($cerrados7, 1) . ' h')
            : 'Sin datos';

        // Área con más tickets en los últimos 7 días
        $areaTop = DB::table('tickets')
            ->where('created_at', '>=', $hace7)
            ->selectRaw('area, COUNT(*) as total')
            ->groupBy('area')
            ->orderByDesc('total')
            ->first();

        // Tipo con más tickets en los últimos 7 días
        $tipoTop = DB::table('tickets')
            ->where('created_at', '>=', $hace7)
            ->selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->first();

        return view('tickets::index', compact(
            'tickets', 'porEstado', 'tecnicos', 'areas', 'comentariosPorTicket',
            'promedioResolucion', 'areaTop', 'tipoTop'
        ));
    }

    public function cambiarEstado(Request $request, int $id)
    {
        $validated = $request->validate(['estado' => 'required|integer|in:0,1,2']);
        $estado    = $validated['estado'];

        $extra = [];
        if ($estado === 1) $extra = ['atendido_at' => now(), 'atendido_by' => Auth::user()->email];
        if ($estado === 2) $extra = ['cerrado_at'  => now(), 'cerrado_by'  => Auth::user()->email];

        DB::table('tickets')->where('id', $id)->update(array_merge(['estado' => $estado], $extra));

        return response()->json(['ok' => true, 'estado' => $estado]);
    }

    public function comentar(Request $request, int $id)
    {
        $validated = $request->validate(['texto' => 'required|string|max:1000']);

        $cId = DB::table('ticket_comentarios')->insertGetId([
            'ticket_id'    => $id,
            'autor_nombre' => Auth::user()->name,
            'autor_email'  => Auth::user()->email,
            'texto'        => $validated['texto'],
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return response()->json([
            'ok'        => true,
            'comentario' => DB::table('ticket_comentarios')->find($cId),
        ]);
    }

    public function asignar(Request $request, int $id)
    {
        $request->validate(['tecnico_email' => 'required|email|exists:users,email']);

        DB::table('tickets')->where('id', $id)->update([
            'atendido_by' => $request->tecnico_email,
        ]);

        return response()->json(['ok' => true]);
    }

    public function conteo()
    {
        return response()->json([
            'total'    => DB::table('tickets')->count(),
            'abiertos' => DB::table('tickets')->where('estado', 0)->count(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Formulario público
    // ─────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $areas = DB::table('areas')->orderBy('nombre')->pluck('nombre');
        $tipos = DB::table('tipos')->orderBy('nombre')->pluck('nombre');

        $ip  = $this->clientIp($request);
        $mac = $this->macFromIp($ip);

        $autofillCorreo = Auth::check() ? Auth::user()->email : null;

        $view = $autofillCorreo ? 'tickets::create-internal' : 'tickets::create';

        return view($view, compact('areas', 'tipos', 'ip', 'mac', 'autofillCorreo'));
    }

    public function store(Request $request)
    {
        $ip  = $this->clientIp($request);
        $mac = $this->macFromIp($ip);

        $validated = $request->validate([
            'correo'      => ['required', 'email', 'exists:users,email'],
            'tipo'        => ['required', 'exists:tipos,nombre'],
            'area'        => ['required', 'exists:areas,nombre'],
            'descripcion' => [
                'required', 'min:5',
                function ($attr, $val, $fail) {
                    if (preg_match('/(cambi|olvid|re?esta|reset|perdi|nueva|solicit|reconoc|se?p[ae]|acuerd|recuerd|nuev|crea|proble).*(contra|clav)/i', $val)
                        || preg_match('/.*(contra|clav).*/i', $val)) {
                        $fail('Los trámites de contraseñas deben solicitarse por correo electrónico.');
                    }
                    if (preg_match('/(sin acce|(no.*(pued|permite|deja|bloque).*(ingres|entr|acce).*)).*(correo|cuenta|usuari)/i', $val)
                        || preg_match('/(crea|alta|nuev|baja|elimina|cambi).*(correo|cuenta|usuari)/i', $val)
                        || preg_match('/.*(correo|cuenta|usuari).*/i', $val)) {
                        $fail('Los trámites de cuentas deben solicitarse mediante oficio.');
                    }
                },
            ],
        ], [
            'correo.required'      => 'El correo es obligatorio.',
            'correo.email'         => 'Ingresa un correo institucional válido.',
            'correo.exists'        => 'Este correo no está registrado en el sistema.',
            'tipo.required'        => 'Selecciona un tipo de incidente.',
            'tipo.exists'          => 'El tipo seleccionado no es válido.',
            'area.required'        => 'Selecciona el área donde ocurrió el incidente.',
            'area.exists'          => 'El área seleccionada no es válida.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.min'      => 'La descripción debe tener al menos :min caracteres.',
        ]);

        $empleado = DB::table('users')->where('email', $validated['correo'])->first();

        Ticket::create([
            'nombre'      => trim($empleado->name . ' ' . $empleado->apellido_paterno . ' ' . $empleado->apellido_materno),
            'correo'      => $validated['correo'],
            'ip'          => $ip,
            'mac'         => $mac,
            'area'        => $validated['area'],
            'tipo'        => $validated['tipo'],
            'descripcion' => $validated['descripcion'],
            'estado'      => 0,
        ]);

        return redirect()->route('tickets.create')
            ->with('success', '¡Ticket enviado! El equipo de soporte lo atenderá a la brevedad.');
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────

    private function clientIp(Request $request): string
    {
        return $request->header('X-Forwarded-For')
            ? explode(',', $request->header('X-Forwarded-For'))[0]
            : $request->ip();
    }

    private function macFromIp(string $ip): ?string
    {
        $safe = escapeshellarg($ip);
        $out  = @shell_exec("arp -n $safe 2>/dev/null");

        if (!$out || !str_contains($out, ':')) {
            $out = @shell_exec("arp -a $safe 2>/dev/null");
        }

        if ($out && preg_match('/([0-9a-f]{2}[:\-][0-9a-f]{2}[:\-][0-9a-f]{2}[:\-][0-9a-f]{2}[:\-][0-9a-f]{2}[:\-][0-9a-f]{2})/i', $out, $m)) {
            return strtoupper(str_replace(':', '-', $m[1]));
        }

        return null;
    }
}
