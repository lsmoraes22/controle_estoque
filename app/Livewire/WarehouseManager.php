<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Warehouse;

class WarehouseManager extends Component
{
    use WithPagination;

    public $warehouse, $warehouseId, $enabled = true, $multiple;
    public $screenAction = 'table';
    public $warehouseToDelete;
    public $confirmingDeletion;
    public $search = '';
    public $fields = [];
    public $foreignFields = [];

    protected $rules = [
        'warehouse.warehouse' => 'required|max:1|unique:warehouses,warehouse',
        'warehouse.description' => 'required|string|max:50',
        'warehouse.multiple' => 'boolean',
        'warehouse.weight_max_alveolus' => 'nullable|integer',
        'warehouse.enabled' => 'boolean',
        'warehouse.type' => 'required|in:P,V'
    ];

    public function mount()
    {
        $this->resetFields();
        $warehouse = new Warehouse();
        $this->fields = $warehouse->getTableFields();
        $this->foreignFields = $warehouse->getForeignField();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $warehouses = Warehouse::where('description', 'like', '%'.$this->search.'%')
            ->paginate(10);
        // Loop através de cada warehouse e definir o id como warehouse
        foreach ($warehouses as $warehouse) {
            $warehouse->id = $warehouse->warehouse;
        }
        return view('livewire.warehouse-management', [
            'warehouses' => $warehouses,
        ]);
    }

    public function create()
    {
        if(!$this->verifyReadOnly()){  
            $this->resetFields();
            $this->screenAction = 'create';
        }
    }

    public function store()
    {
        if(!$this->verifyReadOnly()){  
            $this->validate();

            Warehouse::create($this->warehouse);

            session()->flash('message', 'Warehouse created successfully.');
            $this->resetFields();
            $this->screenAction = 'table';
        }
    }

    public function edit($id)
    {
        if(!$this->verifyReadOnly()){ 
            $warehouse = Warehouse::find($id);
            $this->warehouseId = $warehouse->warehouse;
            $this->warehouse = $warehouse->toArray();
            $this->warehouse['multiple']    = (bool) $warehouse->multiple; // Garante que o valor booleano seja passado corretamente
            $this->warehouse['enabled']     = (bool) $warehouse->enabled; // Garante que o valor booleano seja passado corretamente
            $this->screenAction = 'edit';
        }
    }

    public function confirmDeletion($id)
    {
        if(!$this->verifyReadOnly()){
            $this->warehouseToDelete = $id;
            $this->confirmingDeletion = true;
        }
    }

    public function update()
    {
        if(!$this->verifyReadOnly()){  
            $this->validate();

            $warehouse = Warehouse::find($this->warehouseId);
            $warehouse->update($this->warehouse);

            session()->flash('message', 'Warehouse updated successfully.');
            $this->resetFields();
            $this->screenAction = 'table';
        }
    }

    public function delete($id)
    {
        if(!$this->verifyReadOnly()){ 
            Warehouse::find($id)->delete();
            session()->flash('message', 'Warehouse deleted successfully.');
            $this->confirmingDeletion = false;
        }
    }

    private function resetFields()
    {
        $this->warehouse = [
            'warehouse' => '',
            'multiple' => false,
            'weight_max_alveolus' => null,
            'type' => null,
            'enabled' => true,
        ];
        $this->warehouseId = null;
        $this->enabled = false;
    }

    public function showTable(){
        $this->screenAction = 'table';
    }

    private function verifyReadOnly(){
        if(session('ReadWriteSession') == 'R'){
            session()->flash('messageError', "Your current permission is 'readonly'.");
            return true;
        }
        return false;
    }
}
