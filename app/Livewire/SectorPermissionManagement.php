<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sector;
use App\Models\Permission;
use App\Models\SectorPermission;
use Illuminate\Support\Facades\Session;

class SectorPermissionManagement extends Component
{
    use WithPagination;

    public $sector_id, $permission_id, $level, $enabled = true, $read_write, $sectorPermissionId;
    public $screenAction = 'table';
    public $sectorPermissionToDelete = null;
    public $confirmingDeletion = false;
    public $search = '';
    //protected $paginationTheme = 'Tailwind';

    protected $rules = [
        'sector_id' => 'required|exists:sectors,id',
        'permission_id' => 'required|exists:permissions,id',
        'level' => 'required|integer|min:1|max:10',
        'enabled' => 'boolean',
        'read_write' => 'required|in:R,W'
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $sectorPermissions = SectorPermission::query()
            ->whereHas('sector', function($query) {
                $query->where('sector', 'like', '%' . $this->search . '%');
            })
            ->orWhereHas('permission', function($query) {
                $query->where('route', 'like', '%' . $this->search . '%');
            })
            ->paginate(10);

        return view('livewire.sector-permission-management', [
            'sectorPermissions' => $sectorPermissions,
            'sectors' => Sector::all(),
            'permissions' => Permission::all()
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
            
            // Verifique se a combinação sector_id e permission_id já existe
            if (SectorPermission::where('sector_id', $this->sector_id)
                ->where('permission_id', $this->permission_id)
                ->where('read_write', $this->read_write)
                ->exists()) {
                session()->flash('messageError', 'This sector, permission and read/write combination already exists.');
                return;
            }
        
            SectorPermission::create([
                'sector_id' => $this->sector_id,
                'permission_id' => $this->permission_id,
                'level' => $this->level,
                'enabled' => $this->enabled,
                'read_write' => $this->read_write,
                
            ]);
        
            session()->flash('message', 'Permission created successfully.');
            $this->resetFields();
            $this->screenAction = 'table';
        }
    }
    
    public function confirmDeletion($id)
    {
        if(!$this->verifyReadOnly()){ 
            $this->sectorPermissionToDelete = $id;
            $this->confirmingDeletion = true;
         }
    }

    public function edit($id)
    {
        if($this->verifyReadOnly()){ return; }
        $sectorPermission = SectorPermission::find($id);
        $this->sectorPermissionId = $sectorPermission->id;
        $this->sector_id = $sectorPermission->sector_id;
        $this->permission_id = $sectorPermission->permission_id;
        $this->level = $sectorPermission->level;
        $this->enabled = $sectorPermission->enabled;
        $this->read_write = $sectorPermission->read_write;
        $this->screenAction = 'edit';
    }

    public function update()
    {
        if($this->verifyReadOnly()){ return; }
        $this->validate();

        // Verifique se a combinação sector_id e permission_id já existe para outros registros
        if (SectorPermission::where('sector_id', $this->sector_id)
            ->where('permission_id', $this->permission_id)
            ->where('read_write', $this->read_write)
            ->where('id', '!=', $this->sectorPermissionId)
            ->exists()) {
            session()->flash('messageError', 'This sector, permission and read/write combination already exists.');
            return;
        }

        $sectorPermission = SectorPermission::find($this->sectorPermissionId);
        $sectorPermission->sector_id = $this->sector_id;
        $sectorPermission->permission_id = $this->permission_id;
        $sectorPermission->level = $this->level;
        $sectorPermission->enabled = $this->enabled;
        $sectorPermission->read_write = $this->read_write;
        $sectorPermission->save();

        session()->flash('message', 'Permission updated successfully.');
        $this->resetFields();
        $this->screenAction = 'table';
    }

    public function delete($id)
    {
        if(!$this->verifyReadOnly()){
            SectorPermission::find($id)->delete();
            session()->flash('message', 'Permission deleted successfully.');
            $this->confirmingDeletion = false;
        }
    }

    private function resetFields()
    {
        $this->sector_id = '';
        $this->permission_id = '';
        $this->level = '';
        $this->enabled = true;
        $this->read_write = null;
        $this->sectorPermissionId = null;
    }

    public function showTable()
    {
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
