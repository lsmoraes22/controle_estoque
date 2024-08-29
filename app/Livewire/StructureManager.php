<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models as M;

class StructureManager extends Component
{
    use WithPagination;

    public $structure;
    public $structureId, $warehouse, $hall, $position, $level, $filled = false, $immobilized = false, $enabled = true;
    public $search = '';
    public $screenAction = 'table';
    public $confirmingDeletion = false;
    public $structureToDelete;

    protected $rules = [
        'warehouse' => 'required|string',
        'hall' => 'required|integer',
        'position' => 'required|integer',
        'level' => 'required|integer',
        'filled' => 'boolean',
        'immobilized' => 'boolean',
        'enabled' => 'boolean',
    ];

    public $fields = [];
    public $foreignFields = [];

    public function mount(){
        $this->resetFields();
        $structure = new M\Structure();
        $this->fields = $structure->getTableFields();
        $this->foreignFields = $structure->getForeignField();
    }

    public function render()
    {
        $structures = M\Structure::where('id', 'like', '%'.$this->search.'%')
            ->paginate(10);
        // Loop através de cada warehouse e definir o id como warehouse
        
        $warehouses = M\Warehouse::all();
        return view('livewire.structure-manager', [
            'structures' => $structures,
            'warehouses' => $warehouses
        ]);
    }

    public function create()
    {
        $this->resetFields();
        $this->screenAction = 'create';
    }

    public function store()
    {
        $this->validate();

        M\Structure::create([
            'id' => $this->warehouse . str_pad($this->hall,6,'0',STR_PAD_LEFT) . str_pad($this->position,6,'0',STR_PAD_LEFT) . str_pad($this->level,4,'0',STR_PAD_LEFT),
            'warehouse' => $this->warehouse,
            'hall' => $this->hall,
            'position' => $this->position,
            'level' => $this->level,
            'filled' => $this->filled,
            'immobilized' => $this->immobilized,
            'enabled' => $this->enabled,
        ]);

        session()->flash('message', 'Structure created successfully.');
        $this->resetFields();
        $this->screenAction = 'table';
    }

    public function edit($id)
    {
        $structure = M\Structure::where('id', $id)->firstOrFail();
        $this->structureId = $structure->id;
        $this->warehouse = $structure->warehouse;
        $this->hall = $structure->hall;
        $this->position = $structure->position;
        $this->level = $structure->level;
        $this->enabled = (bool) $structure->enabled;
        $this->immobilized = (bool) $structure->immobilized;
        $this->screenAction = 'edit';
    }

    public function update()
    {
        $this->validate();

        $structure = M\Structure::where('id', $this->id)
            ->firstOrFail();

        $structure->update([
            'filled' => $this->filled,
            'immobilized' => $this->immobilized,
            'enabled' => $this->enabled,
        ]);

        session()->flash('message', 'Structure updated successfully.');
        $this->resetFields();
        $this->screenAction = 'table';
    }

    public function confirmDeletion($id)
    {
        $this->structureToDelete = $id;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        $structure = M\Structure::where('id', $this->structureToDelete)->firstOrFail();
        $structure->delete();

        session()->flash('message', 'Structure deleted successfully.');
        $this->confirmingDeletion = false;
        $this->resetFields();
    }

    private function resetFields()
    {
        $this->warehouse = '';
        $this->hall = '';
        $this->position = '';
        $this->level = '';
        $this->filled = false;
        $this->immobilized = false;
        $this->enabled = true;
        $this->screenAction = 'table';
    }

    public function showTable(){
        $this->screenAction = 'table';
    }

}
