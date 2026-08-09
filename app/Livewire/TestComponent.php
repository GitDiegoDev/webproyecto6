<?php

namespace App\Livewire;

use Livewire\Component;

class TestComponent extends Component
{
    public $message = '¡Hola desde Livewire!';
    public $showExtra = false;

    public function toggleExtra()
    {
        $this->showExtra = !$this->showExtra;
    }

    public function render()
    {
        return view('livewire.test-component');
    }
}
