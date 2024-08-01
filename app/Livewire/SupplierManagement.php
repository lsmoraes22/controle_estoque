<?php 

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Supplier;

class SupplierManagement extends Component
{
    use WithPagination;

    public $supplier, $address, $phone1, $phone2, $phone3, $documents, $enable = true, $supplierId;
    public $screenAction = 'table';
    public $supplierToDelete = null;
    public $confirmingDeletion = false;
    public $search = '';
    protected $rules = [
        'supplier' => 'required|string|max:15',
        'address' => 'required|string',
        'phone1' => 'required|string|max:15',
        'phone2' => 'nullable|string|max:15',
        'phone3' => 'nullable|string|max:15',
        'documents' => 'required|string|max:15',
        'enable' => 'boolean'
    ];

    public function render()
    {
        $suppliers = Supplier::query()
            ->where('supplier', 'like', '%'.$this->search.'%')
            ->orWhere('address', 'like', '%' . $this->search . '%')
            ->paginate(10);
        return view('livewire.supplier-management', [
            'suppliers' => $suppliers
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function create()
    {
        $this->resetFields();
        $this->screenAction = 'create';
    }

    public function store()
    {
        $this->validate();

        Supplier::create([
            'supplier' => $this->supplier,
            'address' => $this->address,
            'phone1' => $this->phone1,
            'phone2' => $this->phone2,
            'phone3' => $this->phone3,
            'documents' => $this->documents,
            'enable' => $this->enable,
        ]);

        session()->flash('message', 'Supplier created successfully.');
        $this->resetFields();
        $this->screenAction = 'table';
    }

    public function confirmDeletion($id)
    {
        $this->supplierToDelete = $id;
        $this->confirmingDeletion = true;
    }
    public function edit($id)
    {
        $supplier = Supplier::find($id);
        $this->supplierId = $supplier->id;
        $this->supplier = $supplier->supplier;
        $this->address = $supplier->address;
        $this->phone1 = $supplier->phone1;
        $this->phone2 = $supplier->phone2;
        $this->phone3 = $supplier->phone3;
        $this->documents = $supplier->documents;
        $this->enable = $supplier->enable;
        $this->screenAction = 'edit';
    }

    public function update()
    {
        $this->validate([
            'supplier' => 'required|string|max:15',
            'address' => 'required|string',
            'phone1' => 'required|string|max:15',
            'phone2' => 'nullable|string|max:15',
            'phone3' => 'nullable|string|max:15',
            'documents' => 'required|string|max:15',
            'enable' => 'boolean'
        ]);

        $supplier = Supplier::find($this->supplierId);
        $supplier->supplier = $this->supplier;
        $supplier->address = $this->address;
        $supplier->phone1 = $this->phone1;
        $supplier->phone2 = $this->phone2;
        $supplier->phone3 = $this->phone3;
        $supplier->documents = $this->documents;
        $supplier->enable = $this->enable;
        $supplier->save();

        session()->flash('message', 'Supplier updated successfully.');
        $this->resetFields();
        $this->screenAction = 'table';
    }

    public function delete($id)
    {
        Supplier::find($id)->delete();
        session()->flash('message', 'Supplier deleted successfully.');
        $this->confirmingDeletion = false;
    }

    private function resetFields()
    {
        $this->supplier = '';
        $this->address = '';
        $this->phone1 = '';
        $this->phone2 = '';
        $this->phone3 = '';
        $this->documents = '';
        $this->enable = true;
        $this->supplierId = null;
    }

    public function showTable(){
        $this->screenAction = 'table';
    }
}
