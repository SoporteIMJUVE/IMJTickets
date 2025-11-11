<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Livewire\Component;

class Navbar extends Component
{
    public $showCentralNav = false;
    
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

    public function mount()
    {
        // evaluar ruta una sola vez al montar el componente
        $this->showCentralNav = request()->routeIs('tickets.user') || request()->routeIs('tickets.index');
    }

    public function render()
    {
        return view('livewire.navbar');
    }
}
