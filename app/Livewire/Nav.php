<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Permission;

class Nav extends Component
{
    public function render()
    {
        $nav = [];
        $permissions = Permission::all();
        foreach ($permissions as $permission){
            $nav[$permission->menu] = $permission->route;
        }
        return view('livewire.nav',['nav' =>$nav]);
    }
}
