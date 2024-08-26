<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;

class ProductManager extends Component
{
    use WithPagination;

    public $product, $productId, $enabled = true;
    public $screenAction = 'table';
    public $productToDelete;
    public $confirmingDeletion;
    public $search = '';
    public $fields = null;
    public $foreignFields = null;

    protected $rules = [
        'product.description' => 'required|string|max:255',
        'product.sale_price' => 'nullable|numeric',
        'product.purchase_price' => 'nullable|numeric',
        'product.category_id' => 'required|integer',
        'product.unit' => 'required|string|max:10',
        'product.unit_per_box' => 'nullable|integer',
        'product.box_ballast' => 'nullable|integer',
        'product.ballast_per_layer' => 'nullable|integer',
        'product.box_weight' => 'nullable|numeric',
        'product.shelflife' => 'nullable|date',
        'product.supplier_default_id' => 'nullable|integer',
        'product.abc_curve' => 'nullable|string|max:3',
        'product.enabled' => 'boolean',
    ];

    public function mount()
    {
        $product = new Product();
        $this->fields = $product->getTableFields();
        $this->foreignFields = $product->getForeignField();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::where('description', 'like', '%'.$this->search.'%')
            ->paginate(10);

        return view('livewire.product-management', [
            'products' => $products,
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

            Product::create($this->product);

            session()->flash('message', 'Product created successfully.');
            $this->resetFields();
            $this->screenAction = 'table';
        }
    }

    public function edit($id)
    {
        if(!$this->verifyReadOnly()){ 
            $product = Product::find($id);
            $this->productId = $product->id;
            $this->product = $product->toArray();
            $this->screenAction = 'edit';
        }
    }

    public function confirmDeletion($id)
    {
        if(!$this->verifyReadOnly()){
            $this->productToDelete = $id;
            $this->confirmingDeletion = true;
        }
    }

    public function update()
    {
        if(!$this->verifyReadOnly()){  
            $this->validate();

            $product = Product::find($this->productId);
            $product->update($this->product);

            session()->flash('message', 'Product updated successfully.');
            $this->resetFields();
            $this->screenAction = 'table';
        }
    }

    public function delete($id)
    {
        if(!$this->verifyReadOnly()){ 
            Product::find($id)->delete();
            session()->flash('message', 'Product deleted successfully.');
            $this->confirmingDeletion = false;
        }
    }

    private function resetFields()
    {
        $this->product = [
            'description' => '',
            'sale_price' => null,
            'purchase_price' => null,
            'category_id' => null,
            'unit' => '',
            'unit_per_box' => null,
            'box_ballast' => null,
            'ballast_per_layer' => null,
            'box_weight' => null,
            'shelflife' => null,
            'supplier_default_id' => null,
            'abc_curve' => '',
            'enabled' => true,
        ];
        $this->productId = null;
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
