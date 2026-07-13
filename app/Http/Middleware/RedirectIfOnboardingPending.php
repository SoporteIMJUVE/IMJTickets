<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registrado sobre TODO el grupo 'web' (ver bootstrap/app.php) — así cubre
 * cualquier ruta, incluidas las públicas (ej. tickets.create) si la persona
 * sigue logueada con el paso 2 pendiente. Para cuentas admin o ya
 * onboardeadas, needsOnboarding() es false y este middleware es un no-op.
 */
class RedirectIfOnboardingPending
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->needsOnboarding()
            && !$request->routeIs(['perfil', 'perfil.completar', 'logout'])) {
            return redirect()->route('perfil');
        }

        return $next($request);
    }
}
