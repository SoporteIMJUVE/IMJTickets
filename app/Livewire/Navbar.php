<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Livewire\Component;

class Navbar extends Component
{
    public $showCentralNav = false;
    public $showExportButtons = false;

    public $wordSearch = '';

    # Funcion predefinida de Livewire que se ejecuta cuando se actualiza la propiedad wordSearch
    public function updatedWordSearch()
    {
        $this->dispatch('emitSearch', wordSearch: $this->wordSearch);
    }
    
    public function emitExcel()
    {
        $this->dispatch('emitExcel');
    }

    public function emitPdf()
    {
        $this->dispatch('emitPdf');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redireccion full reload
        return redirect()->to(route("bienvenida"));
    }

    public function checkNew()
    {
        if (auth()->user()->new_ticket_sound) {
            $this->dispatch('newTicketSound');
            auth()->user()->update(['new_ticket_sound' => false]);
        }
    }

    public function clearNew($redirect = false)
    {
        auth()->user()->update(['new_ticket_alert' => false]);
        
        if ($redirect) {
            return redirect()->to(route("tickets.index"));
        }
    }

    public function mount()
    {
        // evaluar ruta una sola vez al montar el componente
        $this->showCentralNav = request()->routeIs('tickets.user') || request()->routeIs('tickets.index') || request()->routeIs('admin.validar-correos');
        $this->showExportButtons = request()->routeIs('tickets.*');
    }

    public function render()
    {
        return view('livewire.navbar');
    }
}
