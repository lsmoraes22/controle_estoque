<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class RulesEditor extends Component
{
    public $rules = [];
    public $rulesPath = null;

    public function mount()
    {
        $this->rulesPath = Storage::path('xml/nfs/rules.json');
        // Tentar carregar as regras do cache
        $this->rules = Cache::get('XmlNfRules', function () {
            $this->loadRules(); // Se não estiver em cache, carregar do arquivo
            return $this->rules;
        });
    }

    public function loadRules()
    {
        // Carregar as regras do arquivo rules.json
        if (File::exists($this->rulesPath)) {
            $this->rules = json_decode(File::get($this->rulesPath), true);
        } else {
            $this->rules = [];
        }
    }

    public function saveRules()
    {
        // Salvar as regras editadas no arquivo rules.json
        File::put($this->rulesPath, json_encode($this->rules, JSON_PRETTY_PRINT));

        // Atualizar o cache
        Cache::put('XmlNfRules', $this->rules);

        // Mensagem de sucesso
        session()->flash('message', 'Regras salvas com sucesso e cache atualizado!');
    }

    public function render()
    {
        return view('livewire.rules-editor');
    }
}
