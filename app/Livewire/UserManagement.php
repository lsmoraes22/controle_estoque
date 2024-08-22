<?php 

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Sector;  
use Illuminate\Support\Facades\Hash;

class UserManagement extends Component
{
    use WithPagination;

    public $name, $email, $sector_id, $password, $password_confirmation, $level, $userId, $enabled = false;
    public $screenAction = 'table';
    public $userToDelete = null ;
    public $confirmingDeletion = false;
    public $search = '';
    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email',
        'sector_id' => 'required|exists:sectors,id',
        'password' => 'required|string|min:8|confirmed',
        'enabled' => 'boolean',
        'level' => 'integer|min:1|max:10'
    ];
    public $fields = [];
    public $foreignFields = [];

    public function mount()
    {
        $user = new User();
        $this->fields = $user->getTableFields();
        $this->foreignFields = $user->getForeignField();
    }
    public function render()
    {
        $users = User::query()
            ->whereHas('sector', function($query) {
                $query->where('sector', 'like', '%' . $this->search . '%');
            })
            ->orWhere('name', 'like', '%'.$this->search.'%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->paginate(10);
        return view('livewire.user-management', [
            'users' => $users,
            'sectors' => Sector::all(),
            'fields' => $this->fields,
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function create()
    {
        if($this->verifyReadOnly()){ return; }
        $this->resetFields();
        $this->screenAction = 'create';
    }

    public function store()
    {
        if($this->verifyReadOnly()){ return; }
        $this->validate();

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'sector_id' => $this->sector_id,
            'password' => Hash::make($this->password),
            'enabled' => $this->enabled,
            'level' => $this->level,
        ]);

        session()->flash('message', 'User created successfully.');
        $this->resetFields();
        $this->screenAction = 'table';
    }

    public function confirmDeletion($id)
    {
        if($this->verifyReadOnly()){ return; }
        $this->userToDelete = $id;
        $this->confirmingDeletion = true;
    }
    public function edit($id)
    {
        if($this->verifyReadOnly()){ return; }
        $user = User::find($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->sector_id = $user->sector_id;
        $this->enabled = (bool)$user->enabled; // Garantir que seja um booleano
        $this->level = $user->level;
        $this->screenAction = 'edit';
    }

    public function update()
    {
        if($this->verifyReadOnly()){ return; }
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $this->userId,
            'sector_id' => 'required|exists:sectors,id',
            'password' => 'nullable|string|min:8|confirmed',
            'enabled' => 'boolean',
        ]);

        $user = User::find($this->userId);
        $user->name = $this->name;
        $user->email = $this->email;
        $user->sector_id = $this->sector_id;
        $user->enabled = $this->enabled;
        $user->level = $this->level;
        if ($this->password) {
            $user->password = Hash::make($this->password);
        }
        $user->save();

        session()->flash('message', 'User updated successfully.');
        $this->resetFields();
        $this->screenAction = 'table';
    }

    public function delete($id)
    {
        if($this->verifyReadOnly()){ return; }
        User::find($id)->delete();
        session()->flash('message', 'User deleted successfully.');
        $this->confirmingDeletion = false;
    }

    private function resetFields()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->enabled = false;
        $this->sector_id = null;
        $this->userId = null;
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

