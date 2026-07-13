<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CoreController extends Controller
{
    public function loginForm()
    {
        return view('core::auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return Auth::user()->isAdmin()
                ? redirect()->intended(route('dashboard'))
                : redirect()->route('perfil');
        }

        return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // ─── Paso 2: primer acceso obligatorio + código de recuperación ──────────

    private const DOMINIO_INSTITUCIONAL = 'imjuventud.gob.mx';

    public function perfil()
    {
        if (Auth::user()->needsOnboarding()) {
            return view('core::onboarding');
        }

        return view('core::perfil');
    }

    /**
     * Recibe el correo (solo la parte local, el dominio siempre lo pone el
     * servidor), la contraseña nueva en texto plano (normal, por HTTPS, se
     * hashea aquí igual que cualquier login) y el hash del código de
     * recuperación ya calculado en el navegador. Responde JSON porque el
     * frontend solo genera el QR/PDF si esto confirma éxito.
     */
    public function completarPrimerAcceso(Request $request)
    {
        $validated = $request->validate([
            'correo_local' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9._-]+$/i'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'hash'         => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/', 'unique:users,recovery_code_hash'],
        ]);

        $correo = strtolower($validated['correo_local']) . '@' . self::DOMINIO_INSTITUCIONAL;

        if (DB::table('users')->where('email', $correo)->where('id', '!=', Auth::id())->exists()) {
            return response()->json([
                'errors' => ['correo_local' => ['Ese correo ya está en uso.']],
            ], 422);
        }

        DB::table('users')->where('id', Auth::id())->update([
            'email'              => $correo,
            'password'           => Hash::make($validated['password']),
            'recovery_code_hash' => $validated['hash'],
            'updated_at'         => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Login con el código de recuperación — la única forma de volver a
     * entrar si se olvida la contraseña, o el mecanismo de primer acceso
     * si nunca se completó el paso anterior desde este navegador.
     */
    public function loginPorCodigo(string $codigo)
    {
        $user = DB::table('users')->where('recovery_code_hash', $codigo)->first();

        if (!$user) {
            return view('core::codigo-invalido');
        }

        Auth::loginUsingId($user->id);
        request()->session()->regenerate();

        return redirect()->route('perfil');
    }

    public function cambiarPassword(Request $request)
    {
        $validated = $request->validate([
            'codigo_recuperacion' => ['required', 'string'],
            'password'            => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $codigo = trim($validated['codigo_recuperacion']);
        if ($codigo === '' || $codigo !== Auth::user()->recovery_code_hash) {
            return back()->withErrors(['codigo_recuperacion' => 'El código de recuperación no es correcto.']);
        }

        DB::table('users')->where('id', Auth::id())->update([
            'password'   => Hash::make($validated['password']),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Contraseña actualizada.');
    }
}
