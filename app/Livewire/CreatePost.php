<?php

namespace App\Livewire;

use Livewire\Component;

class CreatePost extends Component
{
    public function render()
    {
        $posts = (object)[
            (object) ['id' => 1, 'name' => 'Pedro'],
            (object) ['id' => 2, 'name' => 'Lucas']
        ];
        return view('livewire.create-post')->with([
            'posts' => $posts
        ]);
    }
}
