<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use App\Models\Ticket;
use App\Exports\TicketsExcel;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

class GestionarTickets extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $selectedTicket = null;
    public $formKey = 0;
    public $totalTickets = 0;

    public $fileDB;
    public $errorMessage = '';
    public $datosIncompletos = [];

    public $confirmationWord = '';
    public $wordSearch = '';
    public $activeFilters = [];

    public function mount(){
        $this->totalTickets = Ticket::count();
    }

   #[On('emitSearch')]
    public function search($wordSearch)
    {
        $this->wordSearch = $wordSearch;
        //$this->resetPage();
    }

    public function setFilter($field, $value)
    {
        if (isset($this->activeFilters[$field]) && in_array($value, $this->activeFilters[$field])) {
            // Si ya estaba marcado, lo quitamos
            $this->activeFilters[$field] = array_diff($this->activeFilters[$field], [$value]);
        } else {
            // Si no estaba marcado, lo agregamos
            $this->activeFilters[$field][] = $value;
        }
        $this->resetPage();
    }

    public function buildQuery()
    {
        $query = Ticket::query();
        $term = trim($this->wordSearch);

        if (!empty($term)) {
            $termUpper = '%' . mb_strtoupper($term, 'UTF-8') . '%';
            $termLower = '%' . mb_strtolower($term, 'UTF-8') . '%';
            $termId = '%' . $term . '%';

            $query->where(function($q) use ($termUpper, $termLower, $termId) {
                $q->where('id', 'like', $termId)
                  ->orWhere('nombre', 'like', $termUpper)
                  ->orWhere('correo', 'like', $termLower)
                  ->orWhere('descripcion', 'like', $termLower)
                  ->orWhere('area', 'like', $termLower);
            });
        }

        foreach ($this->activeFilters as $field => $values) {
            if (!empty($values)) {
                $query->whereNotIn($field, $values);
            }
        }

        $query->orderBy('created_at', 'desc');

        return $query;
    }

    // Preparar modal de regreso de estado y eliminación de ticket
    public function prepararRegresoEliminaciónTicket($id)
    {
        $this->selectedTicket = Ticket::find($id);
        $this->formKey++;
    }

    // Regresar un ticket al estado anterior
    public function regresarEstado()
    {
        $ticket = Ticket::find($this->selectedTicket->id);
        
        if ($ticket) {
            if ($ticket->estado == 2) {
                // Si está Cerrado (2), regresa a Atendiendo (1)
                $ticket->estado = 1;
                $ticket->cerrado_at = null;
                $ticket->cerrado_by = null;
                $ticket->save();
            } 
            elseif ($ticket->estado == 1) {
                // Si está Atendiendo (1), regresa a Abierto (0)
                $ticket->estado = 0;
                $ticket->atendido_at = null;
                $ticket->atendido_by = null;
                $ticket->save();
            }
            $this->js("document.getElementById('modalRegresarEstado').close();");
            session()->flash('message', "El estado del ticket {$ticket->id} ha sido actualizado");
        }
    }

    // Eliminar un ticket por completo
    public function eliminarTicket()
    {
        $ticket = Ticket::find($this->selectedTicket->id);
        
        if ($ticket) {
            $idCopia = $ticket->id;
            $ticket->delete();
            $this->reset('selectedTicket');
            $this->js("document.getElementById('modalEliminarTicket').close();");
            session()->flash('message', "Ticket {$idCopia} eliminado exitosamente");
        }
    }

    // Cargar BD de tickets
    public function updatedFileDB()
    {
        $this->validate([
            'fileDB' => 'required|file',
        ]);

        $extension = strtolower($this->fileDB->getClientOriginalExtension());

        try {
            // Archivo SQL
            if ($extension === 'sql') {
                $contenidoSql = file_get_contents($this->fileDB->getRealPath());

                if (!str_contains(strtolower($contenidoSql), 'insert into tickets') && !str_contains(strtolower($contenidoSql), 'insert into `tickets`') && !str_contains(strtolower($contenidoSql), 'insert into "tickets"')) {
                    $this->errorMessage = 'El archivo no es un respaldo válido de este sistmea';
                    $this->reset('fileDB');
                    $this->js("setTimeout(() => {document.getElementById('modalErrorFormatoBD').showModal(); }, 150);");
                    return;
                }

                \Illuminate\Support\Facades\DB::unprepared($contenidoSql);
                $this->reset('fileDB');
                session()->flash('message', "Total de tickets cargados: {$contador}");
                return;
            } 
            
            // Archivo Excel o CSV
            if (in_array($extension, ['xlsx', 'xls', 'csv'])) {
                $datos = Excel::toArray(new \stdClass(), $this->fileDB->getRealPath());
                
                if (empty($datos) || empty($datos[0]) || count($datos[0]) < 2) {
                    $this->errorMessage = 'El archivo Excel está en blanco';
                    $this->reset('fileDB');
                    $this->js("setTimeout(() => {document.getElementById('modalErrorFormatoBD').showModal(); }, 150);");
                    return;
                }

                $filas = $datos[0];
                $encabezados = array_map('strtolower', array_map('trim', $filas[0]));
                
                // Encabezados obligatorios
                $columnasObligatorias = [
                    'id', 'nombre', 'correo', 'area', 'tipo', 'descripcion', 
                    'estado', 'created_at', 'updated_at', 'atendido_at', 
                    'cerrado_at', 'comentarios', 'atendido_by', 'cerrado_by'
                ];

                $columnasFaltantes = array_diff($columnasObligatorias, $encabezados);

                if (!empty($columnasFaltantes)) {
                    $this->errorMessage = 'El archivo no es un respaldo válido de este sistema. Faltan las columnas: ' . implode(', ', $columnasFaltantes);
                    $this->reset('fileDB');
                    $this->js("setTimeout(() => {document.getElementById('modalErrorFormatoBD').showModal(); }, 150);");
                    return;
                }

                // Guardar índices
                $idx = [];
                foreach ($columnasObligatorias as $col) {
                    $idx[$col] = array_search($col, $encabezados);
                }

                // Validación de datos incompletos
                $this->datosIncompletos = [];
                $camposObligatorios = ['id', 'nombre', 'correo', 'area', 'tipo', 'descripcion', 'estado', 'created_at', 'updated_at']; //No pueden ser null
                
                for ($i = 1; $i < count($filas); $i++) {
                    $fila = $filas[$i];
                    
                    if (empty(array_filter($fila))) continue; // Se ignorarn las filas en blanco

                    foreach ($camposObligatorios as $campo) {
                        $valor = trim($fila[$idx[$campo]] ?? '');
                        if ($valor === '') {
                            $this->datosIncompletos[] = [
                                'dato' => ($fila[$idx['id']] ?? 'Desconocido (Fila ' . ($i + 1) . ')'),
                                'error' => strtoupper($campo)
                            ];
                        }
                    }
                }

                if (count($this->datosIncompletos) > 0) {
                    $this->reset('fileDB');
                    $this->js("setTimeout(() => {document.getElementById('modalDatosIncompletosBD').showModal(); }, 150);");
                    return;
                }

                // Todo bien
                $contador = 0;
                
                $formatearFecha = function($valor) {
                    $valor = trim($valor ?? '');
                    if (empty($valor)) return null;
                    
                    // Si Excel lo mandó como número serial (ej. 45406.81)
                    if (is_numeric($valor)) {
                        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor)->format('Y-m-d H:i:s');
                    }
                    
                    // Si Excel lo mandó como texto (reemplazamos diagonales por guiones)
                    try {
                        return \Carbon\Carbon::parse(str_replace('/', '-', $valor))->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        return null;
                    }
                };
                for ($i = 1; $i < count($filas); $i++) {
                    $fila = $filas[$i];
                    if (empty(array_filter($fila))) continue; // Se ignorarn las filas en blanco

                    Ticket::insert([
                        'id'          => trim($fila[$idx['id']]),
                        'nombre'      => trim($fila[$idx['nombre']]),
                        'correo'      => trim($fila[$idx['correo']]),
                        'area'        => trim($fila[$idx['area']]),
                        'tipo'        => trim($fila[$idx['tipo']]),
                        'descripcion' => trim($fila[$idx['descripcion']]),
                        'estado'      => trim($fila[$idx['estado']]),
                        'created_at'  => $formatearFecha($fila[$idx['created_at']]),
                        'updated_at'  => $formatearFecha($fila[$idx['updated_at']]),
                        'atendido_at' => $formatearFecha($fila[$idx['atendido_at']] ?? '') ? trim($fila[$idx['atendido_at']]) : null,
                        'cerrado_at'  => $formatearFecha($fila[$idx['cerrado_at']] ?? '') ? trim($fila[$idx['cerrado_at']]) : null,
                        'comentarios' => !empty(trim($fila[$idx['comentarios']] ?? '')) ? trim($fila[$idx['comentarios']]) : null,
                        'atendido_by' => !empty(trim($fila[$idx['atendido_by']] ?? '')) ? trim($fila[$idx['atendido_by']]) : null,
                        'cerrado_by'  => !empty(trim($fila[$idx['cerrado_by']] ?? '')) ? trim($fila[$idx['cerrado_by']]) : null,
                    ]);
                    $contador++;
                }

                $this->reset('fileDB');
                session()->flash('message', "Se cargaron {$contador} tickets");  
            }

        } catch (\Exception $e) {
            $this->addError('fileDB', 'Error crítico: ' . $e->getMessage());
        }
    }

    #[On('preparar-eliminacion-bd')]
    public function prepararEliminacionBD()
    {
        $this->reset('confirmationWord');
        $this->resetValidation();
        $this->formKey++;
    }

    // Exportación y elimimnación de la BD 
    public function exportarEliminar()
    {
        $this->validate([
            'confirmationWord' => 'required',
        ], [
            'confirmationWord.required' => 'Ingresa la palabra para confirmar la eliminación',
        ]);
        if (strtoupper(trim($this->confirmationWord)) !== 'ELIMINAR') {
            $this->addError('confirmationWord', 'La palabra de confirmación no coincide');
            return;
        }
        $tickets = Ticket::orderBy('created_at', 'desc')->get();

        try {
            // Directorio temporal para generar los archivos
            $timestamp = now()->format('Y_m_d_His');
            $tempDir = 'temp_backup_' . $timestamp;
            Storage::disk('local')->makeDirectory($tempDir);

            // Generar Excel
            Excel::store(new TicketsExcel($tickets, true), "{$tempDir}/IMJTickets_Respaldo.xlsx", 'local');

            // Generar PDF
            $pdf = Pdf::loadView('exports.TicketsPDF', ['tickets' => $tickets, 'titulo' => 'Respaldo de Tickets'])->setPaper('letter', 'landscape');
            Storage::disk('local')->put("{$tempDir}/IMJTickets_Respaldo.pdf", $pdf->output());

            //  Generar SQL manualmente
            $sqlContent = "-- ==========================================\n";
            $sqlContent .= "-- RESPALDO DE TICKETS IMJUVE\n";
            $sqlContent .= "-- Generado el: " . now() . "\n";
            $sqlContent .= "-- ==========================================\n\n";
            
            foreach ($tickets as $t) {
                // Escapamos textos para evitar que comillas simples rompan el SQL
                $nombre = addslashes($t->nombre);
                $correo = addslashes($t->correo);
                $area = addslashes($t->area);
                $tipo = addslashes($t->tipo);
                $descripcion = addslashes($t->descripcion);
                $estado = (isset($t->estado) && $t->estado !== '') ? (int)$t->estado : 0;
                $comentarios = addslashes($t->comentarios ?? '');
                
                // Formateamos fechas y valores nulos
                $atendido_at = $t->atendido_at ? "'{$t->atendido_at}'" : "NULL";
                $cerrado_at = $t->cerrado_at ? "'{$t->cerrado_at}'" : "NULL";
                $atendido_by = $t->atendido_by ? "'{$t->atendido_by}'" : "NULL";
                $cerrado_by = $t->cerrado_by ? "'{$t->cerrado_by}'" : "NULL";
                $created_at = $t->created_at ? "'{$t->created_at}'" : "NULL";
                $updated_at = $t->updated_at ? "'{$t->updated_at}'" : "NULL";

                // Sentencia INSERT asegurando todas las columnas
                $sqlContent .= "INSERT INTO tickets (id, nombre, correo, area, tipo, descripcion, estado, created_at, updated_at, atendido_at, cerrado_at, comentarios, atendido_by, cerrado_by) ";
                $sqlContent .= "VALUES ({$t->id}, '{$nombre}', '{$correo}', '{$area}', '{$tipo}', '{$descripcion}', '{$estado}', {$created_at}, {$updated_at}, {$atendido_at}, {$cerrado_at}, '{$comentarios}', {$atendido_by}, {$cerrado_by});\n";
            }
            Storage::disk('local')->put("{$tempDir}/IMJTickets_Respaldo.sql", $sqlContent);

            // Comprimir todo en un archivo .ZIP
            $zipPath = Storage::disk('local')->path("IMJTickets_Respaldo.zip");
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                $zip->addFile(Storage::disk('local')->path("{$tempDir}/IMJTickets_Respaldo.xlsx"), "IMJTickets_Respaldo.xlsx");
                $zip->addFile(Storage::disk('local')->path("{$tempDir}/IMJTickets_Respaldo.pdf"), "IMJTickets_Respaldo.pdf");
                $zip->addFile(Storage::disk('local')->path("{$tempDir}/IMJTickets_Respaldo.sql"), "IMJTickets_Respaldo.sql");
                $zip->close();
            }

            // Vacía la tabla y reinicia el contador
            Ticket::truncate();

            // Se borran los archivos temporales
            Storage::disk('local')->deleteDirectory($tempDir);
            $this->reset('confirmationWord');
            $this->js("document.getElementById('modalExportarEliminarBD').close();");

            return response()->download($zipPath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            $this->addError('confirmationWord', 'Error al generar el respaldo: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $this->totalTickets = Ticket::count();

        return view('livewire.admin.gestionar-tickets', [
            'tickets' => $this->buildQuery()->paginate(10),
            'estados' => Ticket::ESTADOS 
        ]);
    }
}
