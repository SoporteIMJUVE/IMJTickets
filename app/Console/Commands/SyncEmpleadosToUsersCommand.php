<?php

namespace App\Console\Commands;

use App\Services\EmpleadosToUsersMigrator;
use Illuminate\Console\Command;

class SyncEmpleadosToUsersCommand extends Command
{
    protected $signature   = 'app:sync-empleados-users {--dry-run : Solo reporta, no escribe nada}';
    protected $description = 'Fusiona empleados en users y rellena user_id en las tablas relacionadas (paso 1 de la reestructuración de BD)';

    public function handle(EmpleadosToUsersMigrator $migrator): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->line('');
        $this->line('  <fg=yellow>Sincronizando empleados → users</fg=yellow>' . ($dryRun ? ' <fg=cyan>(dry-run)</fg=cyan>' : ''));
        $this->line('');

        $stats = $migrator->run($dryRun);

        $this->line("    Procesados:                        {$stats['procesados']}");
        $this->line("    ✅ Nuevos:                          {$stats['nuevos']}");
        $this->line("    🔗 Fusionados con cuenta existente: {$stats['fusionados']}");
        $this->line("    ⏭  Ya migrados en corrida previa:   {$stats['ya_existian']}");
        $this->line("    ⚠  Con correo placeholder:          {$stats['placeholders']}");
        $this->line('');

        if ($dryRun) {
            $this->warn('  Dry-run: no se escribió nada en la base de datos.');
        } else {
            $this->info('  Sincronización completada.');
        }
        $this->line('');

        return self::SUCCESS;
    }
}
