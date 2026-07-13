<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\EmpleadosToUsersMigrator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin del sistema
        User::firstOrCreate(
            ['email' => 'admin@imjuventud.gob.mx'],
            [
                'name'     => 'Administrador TI',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]
        );

        // Catálogos (sin dependencias externas — van primero)
        $this->call(TicketsCatalogoSeeder::class);

        // Datos del inventario (orden por dependencias FK)
        $this->call(DepartamentosSeeder::class);    // departamentos
        $this->call(EmpleadosSeeder::class);        // empleados + telefonos

        // Fusiona empleados -> users (paso 1 de la reestructuración de BD)
        app(EmpleadosToUsersMigrator::class)->run();

        $this->call(InventarioEquiposSeeder::class); // inventario_equipos
        $this->call(ImpressorasInsumoSeeder::class); // impresoras + insumos
        $this->call(InventarioIpsSeeder::class);     // cat_rangos_ips + inventario_ips_completo
    }
}
