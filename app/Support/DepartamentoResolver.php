<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Resuelve el texto libre de "área" (capturado a mano en el formulario de
 * resguardo) a una fila real de `departamentos`, creándola si hace falta.
 * Si el área coincide con un rango de `cat_rangos_ips`, se usa el nombre
 * oficial de ese rango en vez del texto tal cual se escribió (evita crear
 * departamentos duplicados por abreviaturas: "DRHM" vs "Recursos Humanos").
 */
class DepartamentoResolver
{
    public static function resolveId(string $areaInput): int
    {
        $areaInput    = trim($areaInput);
        $normalizado  = self::normalizar($areaInput);

        // Comparación insensible a acentos en PHP: es común capturar el área
        // sin tildes ("DIRECCION GENERAL") y que no coincida por LIKE con el
        // nombre oficial ("DIRECCIÓN GENERAL"). Las tablas son chicas
        // (catálogos), así que traer todo y comparar en PHP es barato y
        // evita duplicar departamentos por un acento.
        $rango = DB::table('cat_rangos_ips')->get(['area_nombre'])
            ->first(function ($r) use ($normalizado) {
                $rangoNorm = self::normalizar($r->area_nombre);
                return str_contains($rangoNorm, $normalizado) || str_contains($normalizado, $rangoNorm);
            });

        $nombre = $rango->area_nombre ?? $areaInput;

        $existente = DB::table('departamentos')->get(['id_departamento', 'nombre'])
            ->first(fn ($d) => self::normalizar($d->nombre) === self::normalizar($nombre));
        if ($existente) {
            return $existente->id_departamento;
        }

        return DB::table('departamentos')->insertGetId([
            'nombre'     => $nombre,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function normalizar(string $s): string
    {
        $s = mb_strtolower(trim($s));
        return strtr($s, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
        ]);
    }
}
