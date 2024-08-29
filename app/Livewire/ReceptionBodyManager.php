<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models as M;
//{Journal, ReceptionHeader, ReceptionBody, stock, XmlNfHeader, Structure};
class ReceptionBodyManager  extends Component
{
    use WithPagination;

    public $receptionBody;
    public $id, $header, $product_id, $fabrication, $validity, $batch, $quantity, $theoretical, $user_id, $structure_id, $received = false, $status = 'to receive';
    public $search = '';
    public $screenAction = 'table';
    public $confirmingDeletion = false;
    public $receptionBodyToDelete;
    public $inputs; 
    protected $rules = [ 
        'batch' => 'required|string|max:30',
        'quantity' => 'required|numeric', 
        'structure_id' => 'required|integer'
    ];

    public $fields = [];
    public $foreignFields = [];

    public function mount($header)
    {
        $this->resetFields();
        $this->header = $header; // Atribuindo o valor do parâmetro de rota à propriedade pública
        $receptionBody = new M\ReceptionBody();
        $this->fields = $receptionBody->getTableFields();
        $this->foreignFields = $receptionBody->getForeignField();
    }

    public function render()
    {
        $receptionBody = M\ReceptionBody::join('reception_headers', 'reception_headers.id', '=', 'reception_body.header')
        ->where('reception_body.header', $this->header)
        ->where('reception_body.received', $this->received)
        ->select('reception_body.*')
        //->where('status', 'like', '%' . $this->search . '%')
        ->paginate(10);
        foreach($receptionBody as $r){ $r->id = $r->row; }
        $this->inputs = [
            'batch' => [
                'model' => 'batch',
                'type'  => 'text',
                'label' => 'Batch',
            ],
            'quantity' => [
                'model' => 'quantity',
                'type'  => 'text',
                'label' => 'Quantity',
            ],
            'fabrication' => [
                'model' => 'fabrication',
                'type'  => 'text',
                'label' => 'Fabrication',
            ],
            'validity' => [
                'model' => 'validity',
                'type'  => 'text',
                'label' => 'Validity',
            ],
            'structure_id' => [
                'model' => 'structure_id',
                'type'  => 'text',
                'label' => 'Structure',
            ],
            'received' => [
                'model' => 'received',
                'type'  => 'checkbox',
                'label' => 'Received',
            ],
            'button.submit' => [
                'type'  => 'button.submit',
                'caption' => $this->screenAction == 'edit' ? 'Update Reception' : 'Save Reception',
                'iconClass' => 'bi bi-save',
            ],
        ];
        if($this->status === 'received'){
            unset($this->inputs['batch'],
                $this->inputs['quantity'],
                $this->inputs['fabrication'],
                $this->inputs['validity'],
                $this->inputs['structure_id']
            );
        } else {
            unset($this->inputs['received']);
        }
        return view('livewire.reception-body-manager', [
            'receptionBodys' => $receptionBody
        ]);
    }
    public function status_filter($received)
    {
        $this->status = $received ? 'received' : 'to receive';
        $this->received = (bool) $received;
        $this->render();
    }
        /**
     * desativada a function create
    */

    // public function create()
    // {
    //     $this->resetFields();
    //     $this->screenAction = 'create';
    // }

    // public function store()
    // {
    //     $this->validate();

    //     ReceptionHeader::create([
    //         
    //     ]);

    //     session()->flash('message', 'Reception Header created successfully.');
    //     $this->resetFields();
    //     $this->screenAction = 'table';
    // }

    public function edit($row)
    {   
        $receptionBody = M\ReceptionBody::findOrFail($row);
        $this->id = $receptionBody->row;
        $this->header = $receptionBody->header; 
        $this->batch = $receptionBody->batch;
        $this->quantity = $receptionBody->quantity;
        $this->fabrication = $receptionBody->fabrication;
        $this->validity = $receptionBody->validity;
        $this->structure_id = $receptionBody->structure_id;
        $this->screenAction = 'edit';
    }

    public function update()
    {
        $this->validate();
        $receptionHeader = M\ReceptionHeader::findOrFail($this->header);
        $receptionBody = M\receptionBody::findOrFail($this->id);
        if(!$receptionHeader->enabled){ 
            session()->flash('messageError', 'Editing this record is disabled!');
            return redirect("/receptions/$receptionBody->header"); 
        }
        $filled = false;
        $structure = M\Structure::findOrFail($receptionBody->structure_id);
        $warehouse = M\Warehouse::findOrFail($structure->warehouse);
        if($this->status == 'to receive'){
            if($structure->filled && !$warehouse->multiple){
                session()->flash('messageError', 'This position is already filled!');
                return redirect("/receptions/$receptionBody->header");
            }
            $arrReceptionBody = [
                'batch' => $this->batch, 
                'structure_id' => $this->structure_id,
                'quantity' => $this->quantity,
                'user_id' => auth()->id(),
                'received' => true
            ];
            $this->status = 'received';
            $filled = true;
        } else {
            $arrReceptionBody = [
                'received' => $this->received
            ];
            $this->status = 'to receive';
            $w = abs(substr($this->structure_id,0,1));
            $h = abs(substr($this->structure_id,1,6)); 
            $p = abs(substr($this->structure_id,7,6)); 
            $l = abs(substr($this->structure_id,13,4));
            $filled = $structure->countPositionStockFilled($w, $h, $p, $l) > 0 ? true : false;
        }
        $structure->update([ 'filled' => $filled ]);
        $receptionBody->update($arrReceptionBody);
        $receptionHeader->update([ 'status' => 'reception in progress' ]);
        $this->screenAction = 'table';
    }

    /**
     * desabilitada function deletion
     */
    // public function confirmDeletion($row)
    // {
    //     $this->receptionBodyToDelete = $row;
    //     $this->confirmingDeletion = true;
    // }

    // public function delete()
    // {
    //     $receptionHeader = ReceptionHeader::findOrFail($this->receptionHeaderToDelete);
    //     $receptionHeader->delete();

    //     session()->flash('message', 'Reception Header deleted successfully.');
    //     $this->confirmingDeletion = false;
    //     $this->resetFields();
    // }

    private function resetFields()
    {
        $this->batch = null; 
        $this->theoretical = null;
        $this->user_id = null;
        $this->structure_id = null;
    }

    public function showTable()
    {
        $this->screenAction = 'table';
    }

}