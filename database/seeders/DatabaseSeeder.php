<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Area;
use App\Models\Type;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@imjuventud.gob.mx',
        ]);

        Area::create(['nombre' => 'Dirección General']);
        Area::create(['nombre' => 'SDirección de Bienestar y Estimulos a la Juventud']);
        Area::create(['nombre' => 'Dirección de Investigación y Estudios sobre Juventudes']);
        Area::create(['nombre' => 'Dirección de Coordinación Sectorial y Regional']);
        Area::create(['nombre' => 'Dirección de Evaluación y Control']);
        Area::create(['nombre' => 'Dirección de Finanzas']);
        Area::create(['nombre' => 'Dirección de Recursos Humanos y Materiales']);
        Area::create(['nombre' => 'Dirección de Asuntos Jurídicos']);
        Area::create(['nombre' => 'Dirección de Comunicación Social']);
        Area::create(['nombre' => 'Dirección de Auditoría Interna y Quejas']);
        Area::create(['nombre' => 'Subdirección de Sistemas']);

        Type::create(['nombre' => 'Falla en la red']);
        Type::create(['nombre' => 'Problema al imprimir']);
        Type::create(['nombre' => 'Problema al escanear']);
        Type::create(['nombre' => 'Cambio de correo institucional']);
    }
}
