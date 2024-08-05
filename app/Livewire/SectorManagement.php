<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sector;

class SectorManagement extends Component
{
    use WithPagination;

    public $sector, $sectorId, $enabled = true;
    public $screenAction = 'table';
    public $sectorToDelete ;
    public $confirmingDeletion;
    public $search = '';
    
    protected $rules = [
        'sector' => 'required|string|max:15',
    ];

    public $fields = [];
    public $foreignFields = [];

    public function mount()
    {
        $sector = new Sector();
        $this->fields = $sector->getTableFields();
        $this->foreignFields = $sector->getForeignField();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function render()
    {
        $sectors = Sector::Where('sector', 'like', '%'.$this->search.'%')
            ->paginate(10);
        return view('livewire.sector-management', [
            'sectors' => $sectors
            //'enabled' => $sectors->enabled
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
            
            Sector::create([
                'sector' => $this->sector,
                'enabled' => $this->enabled,
            ]);
            
            session()->flash('message', 'Sector created successfully.');
            $this->resetFields();
            $this->screenAction = 'table';
        }
    }

    public function edit($id)
    {
        if(!$this->verifyReadOnly()){ 
            $sector = Sector::find($id);
            $this->sectorId = $sector->id;
            $this->sector = $sector->sector;
            $this->enabled = (bool) $sector->enabled; // Ensure the enabled value is a boolean
            $this->screenAction = 'edit';
        }
    }

    public function confirmDeletion($id)
    {
        if(!$this->verifyReadOnly()){
            $this->sectorToDelete = $id;
            $this->confirmingDeletion = true;
        }
    }
    public function update()
    {
        if(!$this->verifyReadOnly()){  
            $this->validate();
            $sector = Sector::find($this->sectorId);
            $sector->sector = $this->sector;
            $sector->enabled = $this->enabled;
            $sector->save();
            
            session()->flash('message', 'Sector updated successfully.');
            $this->resetFields();
            $this->screenAction = 'table';
        }
    }

    public function delete($id)
    {
        if(!$this->verifyReadOnly()){ 
           
            Sector::find($id)->delete();
            session()->flash('message', 'Sector deleted successfully.');
            $this->confirmingDeletion = false;
        }
    }

    private function resetFields()
    {
        $this->sector = '';
        $this->sectorId = null;
        $this->enabled = false;
    }

    public function showTable(){
        $this->screenAction = 'table';
    }
    private function verifyReadOnly(){
        if(session('ReadWriteSession')=='R'){
            session()->flash('messageError', "Your current permission is 'readonly' ");
            return true;
        }
        return false;
    }

}
