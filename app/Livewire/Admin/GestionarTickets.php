<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
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

    public function mount(){
        $this->totalTickets = Ticket::count();
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
                DB::unprepared(file_get_contents($this->fileDB->getRealPath()));
                $contador = Ticket::count();
                
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
                    $this->errorMessage = 'El archivo no es un respaldo válido. Faltan las columnas: ' . implode(', ', $columnasFaltantes);
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
                for ($i = 1; $i < count($filas); $i++) {
                    $fila = $filas[$i];
                    if (empty(array_filter($fila))) continue; // Se ignorarn las filas en blanco

                    Ticket::create([
                        'id'          => trim($fila[$idx['id']]),
                        'nombre'      => trim($fila[$idx['nombre']]),
                        'correo'      => trim($fila[$idx['correo']]),
                        'area'        => trim($fila[$idx['area']]),
                        'tipo'        => trim($fila[$idx['tipo']]),
                        'descripcion' => trim($fila[$idx['descripcion']]),
                        'estado'      => trim($fila[$idx['estado']]),
                        'created_at'  => trim($fila[$idx['created_at']]),
                        'updated_at'  => trim($fila[$idx['updated_at']]),
                        
                        //Si traen texto lo guarda, si vienen vacíos los pone en NULL
                        'atendido_at' => !empty(trim($fila[$idx['atendido_at']] ?? '')) ? trim($fila[$idx['atendido_at']]) : null,
                        'cerrado_at'  => !empty(trim($fila[$idx['cerrado_at']] ?? '')) ? trim($fila[$idx['cerrado_at']]) : null,
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

    public function render()
    {
        $this->totalTickets = Ticket::count();

        return view('livewire.admin.gestionar-tickets', [
            'tickets' => Ticket::orderBy('created_at', 'desc')->paginate(10)
        ]);
    }
}
