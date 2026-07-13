<?php

namespace App\Services;

use App\Support\NewAccountProvisioner;
use Illuminate\Support\Facades\DB;

/**
 * Fusiona `empleados` (directorio de RRHH, sin login) dentro de `users`
 * (tabla de autenticación de Laravel), dejando `empleados` intacta como
 * staging/backup. Pensado para correrse más de una vez sin duplicar datos:
 * la segunda corrida encuentra las filas ya migradas por email y solo
 * rellena lo que falte.
 */
class EmpleadosToUsersMigrator
{
    private const TABLAS_CON_FK = [
        'telefonos',
        'inventario_equipos',
        'impresoras',
        'inventario_ips_completo',
    ];

    public function run(bool $dryRun = false): array
    {
        $stats = [
            'procesados'    => 0,
            'nuevos'        => 0,
            'fusionados'    => 0,
            'ya_existian'   => 0,
            'placeholders'  => 0,
        ];

        if (!$dryRun) {
            // Normaliza cualquier rol heredado ('tecnico', etc.) — solo 'admin' sobrevive tal cual.
            DB::table('users')->where('role', '!=', 'admin')->update(['role' => 'user']);
        }

        $mapaIdEmpleadoAUserId = [];
        $correosVistos         = [];

        foreach (DB::table('empleados')->orderBy('id_empleado')->get() as $emp) {
            $stats['procesados']++;

            $correoNormalizado = $emp->correo ? mb_strtolower(trim($emp->correo)) : null;
            $usarPlaceholder   = false;

            if (!$correoNormalizado) {
                $usarPlaceholder = true;
            } elseif (isset($correosVistos[$correoNormalizado])) {
                // Correo duplicado dentro de empleados — solo la primera ocurrencia se queda con el real.
                $usarPlaceholder = true;
            } else {
                $correosVistos[$correoNormalizado] = true;
            }

            if ($usarPlaceholder) {
                $stats['placeholders']++;
            }

            $datosEmpleado = [
                'apellido_paterno' => $emp->apellido_paterno,
                'apellido_materno' => $emp->apellido_materno,
                'puesto'           => $emp->puesto,
                'id_departamento'  => $emp->id_departamento,
                'activo'           => $emp->activo,
                'fecha_alta'       => $emp->fecha_alta,
                'fecha_baja'       => $emp->fecha_baja,
            ];

            // ¿Ya existe una cuenta con este correo real? (misma persona con login previo)
            $existente = $usarPlaceholder
                ? null
                : DB::table('users')->whereRaw('LOWER(email) = ?', [$correoNormalizado])->first();

            if ($existente) {
                if (!$dryRun) {
                    DB::table('users')->where('id', $existente->id)
                        ->update($datosEmpleado + ['updated_at' => now()]);
                }
                $mapaIdEmpleadoAUserId[$emp->id_empleado] = $existente->id;
                $stats['fusionados']++;
                continue;
            }

            // Candidato determinístico (mismo en cada corrida) — NO pasa aún por el
            // desempate de colisiones de NewAccountProvisioner, para que la
            // detección de "ya migrado" abajo compare contra el email real que
            // se generó la primera vez, no contra uno nuevo con sufijo distinto.
            $candidatoBase = $usarPlaceholder
                ? sprintf('empleado%s@%s', $emp->id_empleado, NewAccountProvisioner::PLACEHOLDER_DOMAIN)
                : $emp->correo;

            // Re-ejecución: esta fila ya se migró en una corrida anterior (mismo email resultante).
            $yaMigrado = DB::table('users')->whereRaw('LOWER(email) = ?', [mb_strtolower($candidatoBase)])->first();
            if ($yaMigrado) {
                $mapaIdEmpleadoAUserId[$emp->id_empleado] = $yaMigrado->id;
                $stats['ya_existian']++;
                continue;
            }

            if ($dryRun) {
                $stats['nuevos']++;
                continue;
            }

            $email = $usarPlaceholder
                ? NewAccountProvisioner::placeholderEmail($emp->id_empleado)
                : $emp->correo;

            $nuevoId = DB::table('users')->insertGetId([
                'name'     => $emp->nombre,
                'email'    => $email,
                'password' => NewAccountProvisioner::tempPasswordHash(),
                'role'     => 'user',
                ...$datosEmpleado,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $mapaIdEmpleadoAUserId[$emp->id_empleado] = $nuevoId;
            $stats['nuevos']++;
        }

        if (!$dryRun) {
            $this->backfillUserId($mapaIdEmpleadoAUserId);
        }

        return $stats;
    }

    private function backfillUserId(array $mapaIdEmpleadoAUserId): void
    {
        foreach (self::TABLAS_CON_FK as $tabla) {
            foreach ($mapaIdEmpleadoAUserId as $idEmpleado => $userId) {
                DB::table($tabla)
                    ->where('id_empleado', $idEmpleado)
                    ->whereNull('user_id')
                    ->update(['user_id' => $userId]);
            }
        }
    }
}
