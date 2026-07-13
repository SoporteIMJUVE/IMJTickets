<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Genera credenciales de arranque para personas que entran a `users` sin un
 * correo institucional válido a mano (alta manual en CRM o migración masiva
 * de `empleados`). El correo placeholder y la password aleatoria son
 * temporales: el paso 2 (código de recuperación con QR/PDF) es lo que le da
 * acceso real a la cuenta.
 */
class NewAccountProvisioner
{
    public const PLACEHOLDER_DOMAIN = 'placeholder.imjuve.local';

    /**
     * Genera un correo placeholder único que nunca puede chocar con uno
     * institucional real, usando el id de origen como sufijo estable.
     */
    public static function placeholderEmail(int|string $seed): string
    {
        $email = sprintf('empleado%s@%s', $seed, self::PLACEHOLDER_DOMAIN);

        // Salvaguarda por si el seed ya se usó (no debería pasar, pero el
        // dominio placeholder nunca debe producir un choque de unicidad).
        $suffix = 0;
        $candidate = $email;
        while (DB::table('users')->where('email', $candidate)->exists()) {
            $suffix++;
            $candidate = sprintf('empleado%s-%d@%s', $seed, $suffix, self::PLACEHOLDER_DOMAIN);
        }

        return $candidate;
    }

    /**
     * Password aleatoria criptográfica ya hasheada, lista para insertar.
     * El valor en texto plano nunca se retorna ni se loguea.
     */
    public static function tempPasswordHash(): string
    {
        return Hash::make(Str::password(32));
    }
}
