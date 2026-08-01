<?php

namespace App\Console\Commands;

use App\Support\KardexMovimiento;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedEntradasCommand extends Command
{
    protected $signature = 'kardex:seed-entradas
                            {--porcentaje=60 : Porcentaje de equipos sin Entrada a los que se creará el evento (0-100)}
                            {--rollback       : Elimina todos los eventos marcados como seed-pruebas}';

    protected $description = 'Seed de pruebas: crea eventos Entrada (+ Asignación) en movimientos_equipos para simular resguardos institucionales.';

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        $porcentaje = (int) $this->option('porcentaje');
        if ($porcentaje < 1 || $porcentaje > 100) {
            $this->error('--porcentaje debe estar entre 1 y 100.');
            return Command::FAILURE;
        }

        // Admin para registrado_by
        $adminId = DB::table('users')->where('role', 'admin')->value('id');

        // Equipos sin ningún evento Entrada
        $sinEntrada = DB::table('inventario_equipos as eq')
            ->whereNotExists(function ($q) {
                $q->from('movimientos_equipos as m')
                  ->whereColumn('m.activo_id', 'eq.id')
                  ->where('m.tipo_activo', 'equipo')
                  ->where('m.tipo_evento', 'Entrada');
            })
            ->select('eq.id', 'eq.user_id', 'eq.cpu_serie', 'eq.cpu_marca', 'eq.cpu_modelo', 'eq.created_at')
            ->get();

        $total     = $sinEntrada->count();
        $selección = (int) ceil($total * $porcentaje / 100);

        if ($total === 0) {
            $this->info('Todos los equipos ya tienen evento Entrada. Nada que hacer.');
            return Command::SUCCESS;
        }

        $this->info("Equipos sin resguardo encontrados: {$total}");
        $this->info("Se crearán eventos para: {$selección} ({$porcentaje}%)");

        $elegidos = $sinEntrada->shuffle()->take($selección);

        // Cache de nombres de responsables — solo IDs que realmente existen en users
        $userIdsPresentes = DB::table('users')
            ->whereIn('id', $elegidos->pluck('user_id')->filter()->unique())
            ->pluck('id')
            ->flip();

        $nombres = DB::table('users')
            ->whereIn('id', $userIdsPresentes->keys())
            ->select('id', DB::raw("NULLIF(TRIM(COALESCE(name,'') || ' ' || COALESCE(apellido_paterno,'')), '') as nombre_completo"))
            ->pluck('nombre_completo', 'id');

        $bar = $this->output->createProgressBar($selección);
        $bar->start();

        $ahora = now();

        foreach ($elegidos as $eq) {
            // Fecha inventada retroactiva distribuida en los últimos 2 años
            $diasAleatorios = rand(30, 730);
            $fechaEntrada   = $ahora->copy()->subDays($diasAleatorios)->setTime(rand(8, 17), rand(0, 59));

            KardexMovimiento::registrar(
                tipo_activo:   'equipo',
                activo_id:     $eq->id,
                tipo_evento:   'Entrada',
                origen:        'Proveedor',
                destino:       'Subdirección de Sistemas',
                user_from_id:  null,
                user_to_id:    null,
                estado_equipo: 'Almacén',
                notas:         '[seed-pruebas] Importado de registro legado.',
                registrado_by: $adminId,
            );

            // Actualizar manualmente la fecha para que sea retroactiva
            DB::table('movimientos_equipos')
                ->where('activo_id', $eq->id)
                ->where('tipo_activo', 'equipo')
                ->where('tipo_evento', 'Entrada')
                ->where('notas', 'like', '[seed-pruebas]%')
                ->orderByDesc('id')
                ->limit(1)
                ->update(['created_at' => $fechaEntrada, 'updated_at' => $fechaEntrada]);

            // Si tiene responsable Y ese usuario existe en la tabla users
            if ($eq->user_id && $userIdsPresentes->has($eq->user_id)) {
                $nombreResp    = $nombres[$eq->user_id] ?? "Usuario #{$eq->user_id}";
                $fechaAsig     = $fechaEntrada->copy()->addDay();

                KardexMovimiento::registrar(
                    tipo_activo:   'equipo',
                    activo_id:     $eq->id,
                    tipo_evento:   'Asignación',
                    origen:        'Subdirección de Sistemas',
                    destino:       $nombreResp,
                    user_from_id:  null,
                    user_to_id:    $eq->user_id,
                    estado_equipo: 'Asignado',
                    notas:         '[seed-pruebas] Asignación inicial importada de registro legado.',
                    registrado_by: $adminId,
                );

                DB::table('movimientos_equipos')
                    ->where('activo_id', $eq->id)
                    ->where('tipo_activo', 'equipo')
                    ->where('tipo_evento', 'Asignación')
                    ->where('notas', 'like', '[seed-pruebas]%')
                    ->orderByDesc('id')
                    ->limit(1)
                    ->update(['created_at' => $fechaAsig, 'updated_at' => $fechaAsig]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ {$selección} equipos ahora son institucionales (tienen evento Entrada).");
        $this->info("  Restantes sin resguardo: " . ($total - $selección));
        $this->newLine();
        $this->comment('Para deshacer: php artisan kardex:seed-entradas --rollback');

        return Command::SUCCESS;
    }

    private function rollback(): int
    {
        $eliminados = DB::table('movimientos_equipos')
            ->where('notas', 'like', '[seed-pruebas]%')
            ->delete();

        $this->info("✓ Eliminados {$eliminados} eventos de seed-pruebas.");
        return Command::SUCCESS;
    }
}
