<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class StatusForm extends Form
{
    // Escrito con "I" mayúscula para no tener errores
    public $ticketId; 
    public $expectedWord;
    public $confirmationWord = '';

    public function rules()
    {
        return [
            'confirmationWord' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'confirmationWord.required' => 'Ingresa la palabra para cambiar el estado',
        ];
    }

    public function validateStatus()
    {
        $this->validate();
        if(strtolower(trim($this->confirmationWord)) !== strtolower(trim($this->expectedWord))) {
            $this->addError('confirmationWord', 'La palabra no coincide, intente de nuevo');
            return false;
        }
        return true;
    }
}