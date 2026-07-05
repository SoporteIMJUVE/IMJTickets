<?php

namespace Modules\Tickets\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        return view('tickets::index', compact('tickets', 'porEstado'));
    }

    public function create(Request $request)
    {
        $areas = DB::table('areas')->orderBy('nombre')->pluck('nombre');
        $tipos = DB::table('tipos')->orderBy('nombre')->pluck('nombre');

        $ip  = $this->clientIp($request);
        $mac = $this->macFromIp($ip);

        // Si hay sesión activa, buscamos al empleado por su email
        $autofillCorreo = null;
        if (Auth::check()) {
            $emp = DB::table('empleados')
                ->where('correo', Auth::user()->email)
                ->first();
            $autofillCorreo = $emp ? $emp->correo : Auth::user()->email;
        }

        $view = $autofillCorreo ? 'tickets::create-internal' : 'tickets::create';

        return view($view, compact('areas', 'tipos', 'ip', 'mac', 'autofillCorreo'));
    }

    public function store(Request $request)
    {
        $ip  = $this->clientIp($request);
        $mac = $this->macFromIp($ip);

        $validated = $request->validate([
            'correo'      => ['required', 'email', 'exists:empleados,correo'],
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

        $empleado = DB::table('empleados')->where('correo', $validated['correo'])->first();

        Ticket::create([
            'nombre'      => trim($empleado->nombre . ' ' . $empleado->apellido_paterno . ' ' . $empleado->apellido_materno),
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

    // ─── Helpers privados ───────────────────────────────────────────────────

    private function clientIp(Request $request): string
    {
        return $request->header('X-Forwarded-For')
            ? explode(',', $request->header('X-Forwarded-For'))[0]
            : $request->ip();
    }

    private function macFromIp(string $ip): ?string
    {
        // ARP solo funciona si el cliente está en la misma red local que el servidor.
        // En acceso remoto devolverá null — es el comportamiento esperado.
        $safe = escapeshellarg($ip);

        // Linux / macOS
        $out = @shell_exec("arp -n $safe 2>/dev/null");

        // Windows (XAMPP en producción)
        if (!$out || !str_contains($out, ':')) {
            $out = @shell_exec("arp -a $safe 2>/dev/null");
        }

        if ($out && preg_match('/([0-9a-f]{2}[:\-][0-9a-f]{2}[:\-][0-9a-f]{2}[:\-][0-9a-f]{2}[:\-][0-9a-f]{2}[:\-][0-9a-f]{2})/i', $out, $m)) {
            return strtoupper(str_replace(':', '-', $m[1]));
        }

        return null;
    }
}
