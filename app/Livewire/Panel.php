<?php

namespace App\Livewire;

use Livewire\Component;

class Panel extends Component
{
    public $message;
    public function showMessage($message){
        $this->message = $message;
    }
    public function render()
    {
        return view('livewire.panel');
    }
}
