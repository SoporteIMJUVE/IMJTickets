<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketsCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'Dirección General',
            'Secretaría Técnica',
            'Dirección de Recursos Humanos y Materiales',
            'Subdirección de Recursos Materiales',
            'Dirección de Finanzas',
            'Dirección de Coordinación Sectorial y Regional',
            'Dirección de Bienestar y Estímulos a la Juventud',
            'Subdirección de Equidad y Servicios a Jóvenes',
            'Empresas Juveniles',
            'Dirección de Asuntos Jurídicos',
            'Unidad de Transparencia',
            'Órgano Interno de Control',
            'Auditoría Interna',
            'Dirección de Comunicación Social',
            'Subdirección de Sistemas',
            'Soporte Técnico',
            'Dirección de Evaluación y Control',
            'Dirección de Investigación y Estudios sobre Juventud',
        ];

        $tipos = [
            'Soporte de Hardware',
            'Soporte de Software',
            'Conectividad / Red',
            'Cuenta y Accesos',
            'Impresora / Periférico',
            'Teléfono / Extensión',
            'Solicitud de Equipo',
            'Instalación de Aplicación',
            'Correo Institucional',
            'Otro',
        ];

        foreach ($areas as $a) {
            DB::table('areas')->insertOrIgnore(['nombre' => $a]);
        }

        foreach ($tipos as $t) {
            DB::table('tipos')->insertOrIgnore(['nombre' => $t]);
        }

        $this->command->info('Áreas: ' . count($areas) . ' | Tipos: ' . count($tipos));
    }
}
