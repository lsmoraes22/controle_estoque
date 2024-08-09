<?php

namespace App\Livewire;
use Livewire\Component;
use Illuminate\Support\Facades\Request;
class Login extends Component
{
    public function render()
    {
        //echo password_hash('van2019',PASSWORD_BCRYPT);
        return view('livewire.login');
    }
}
