<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models as M;//\{ReceptionHeader, Supplier, XmlNfHeader};

class ReceptionHeaderManager extends Component
{
    use WithPagination;

    public $receptionHeader;
    public $receptionHeaderId, $supplier_id, $xml_nf_header_id, $status = 'to receive', $enabled = true, $componentStatus = 'to receive';
    public $search = '';
    public $screenAction = 'table';
    public $confirmingDeletion = false;
    public $receptionHeaderToDelete;
    public $confirmLowerReceptionId;
    public $confirmLowerReception = false;

    protected $rules = [
        'supplier_id' => 'required|exists:suppliers,id',
        'xml_nf_header_id' => 'required|exists:xml_nf_header,id',
        'status' => 'required|in:to receive,reception in progress,received,on hold,canceled',
        'enabled' => 'boolean',
    ];

    public $fields = [];
    public $foreignFields = [];
    public $options = [
        'Status...' => [
            'caption'   => 'Status...',
            'value'     => ''
        ],
        'to receive' => [
            'caption'   => 'to receive',
            'value'     => 'to receive'
        ],
        'reception in progress' =>[
            'caption' => 'in progress',
            'value' => 'reception in progress'
        ],
        'received' =>[
            'caption' => 'received',
            'value' => 'received'
        ],
        'on hold' =>[
            'caption' => 'on hold',
            'value' => 'on hold'
        ],
        'canceled' =>[
            'caption' => 'canceled',
            'value' => 'canceled'
        ]
    ]; 
    public $inputs = [];

    public function mount()
    {
        $this->resetFields();
        $receptionHeader = new M\ReceptionHeader();
        $this->fields = $receptionHeader->getTableFields();
        $this->foreignFields = $receptionHeader->getForeignField();
    }

    public function render()
    {
        $receptionHeaders = M\ReceptionHeader::join('suppliers', 'suppliers.id', '=', 'reception_headers.supplier_id')
        ->where('suppliers.supplier', 'like', '%' . $this->search . '%')
        ->where('reception_headers.status', '=', $this->status)
        ->select('reception_headers.*')
        ->paginate(10);
        
        $suppliers = M\Supplier::all();
        $xmlNfHeaders = M\XmlNfHeader::all();
        
        $this->inputs = [
            [
                'model' => 'enabled',
                'type'  => 'checkbox',
                'label' => 'Enabled',
            ],
            [
                'model' => 'status',
                'type'  => 'select',
                'label' => 'Status',
                'options' => $this->options
            ],
            [
                'type'  => 'button.submit',
                'caption' => $this->screenAction == 'edit' ? 'Update Reception' : 'Save Reception',
                'iconClass' => 'bi bi-save',
            ],
        ]; 

        return view('livewire.reception-header-manager', [
            'receptionHeaders' => $receptionHeaders,
            'suppliers' => $suppliers,
            'xmlNfHeaders' => $xmlNfHeaders
        ]);
    }

    public function status_filter($status)
    {
        $this->status = $status;
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
    //         'supplier_id' => $this->supplier_id,
    //         'xml_nf_header_id' => $this->xml_nf_header_id,
    //         'status' => $this->status,
    //         'enabled' => $this->enabled,
    //     ]);

    //     session()->flash('message', 'Reception Header created successfully.');
    //     $this->resetFields();
    //     $this->screenAction = 'table';
    // }

    public function edit($id)
    {
        $receptionHeader = M\ReceptionHeader::findOrFail($id);
        $this->receptionHeaderId = $receptionHeader->id;
        $this->supplier_id = $receptionHeader->supplier_id;
        $this->xml_nf_header_id = $receptionHeader->xml_nf_header_id;
        $this->status = $receptionHeader->status;
        $this->enabled = $receptionHeader->enabled;
        $this->screenAction = 'edit';
    }

    public function update()
    {
        $this->validate();
        $receptionHeader = M\ReceptionHeader::findOrFail($this->receptionHeaderId);
        if($this->status!='' && $this->status!=$receptionHeader->status){
            $this->changeStatus($receptionHeader, $this->status);
        } else {
            $receptionHeader->update([ 'enabled' => $this->enabled ]);
        }
        session()->flash('message', 'Reception Header updated successfully.');
        $this->resetFields();
        $this->screenAction = 'table';
    }

    private function updateStatusToReceive($header)
    {
        $receptionHeader = M\ReceptionHeader::findOrFail($header);
        $receptionHeader->update(['status' => 'to receive', 'enabled' => $this->enabled]);
        M\ReceptionBody::where(['header' => $header, 'received' => true])
            ->update(['received' => false]);
    }
    private function updateStatusReceptionInProgress($header){
        $receptionHeader = M\ReceptionHeader::findOrFail($header);
        $receptionHeader->update(['status' => 'reception in progress', 'enabled' => $this->enabled]);
    }

    private function updateStatusOnHold($header){
        $receptionHeader = M\ReceptionHeader::findOrFail($header);
        $receptionHeader->update(['status' => 'on hold', 'enabled' => $this->enabled ]);
    }

    private function updateStatusCanceled($header){
        $receptionHeader = M\ReceptionHeader::findOrFail($header);
        $receptionHeader->update(['status' => 'canceled', 'enabled' => $this->enabled ]);
    }

    public function toConfirmLowerReception($id)
    {
        $this->confirmLowerReceptionId = $id;
        $this->confirmLowerReception = true;
    }

    public function LowerReception()
    {
        $receptionHeader = M\ReceptionHeader::findOrFail($this->confirmLowerReceptionId);
        $receptionHeader->delete();
        $this->confirmingDeletion = false;
        $this->resetFields();
        return session()->flash('message', 'Reception executed successfully.');
    }
    private function updateStatusReceived($header)
    {
        $receptionHeader = M\ReceptionHeader::findOrFail($header);
        $rowsBody = M\ReceptionBody::where(['header' => $header, 'received' => true])->get();
        $contRowsBody = count($rowsBody);
        if($contRowsBody<$receptionHeader->rows) return $this->toConfirmLowerReception($receptionHeader->id) ;
        $receptionHeader->update([ 'status' => 'received' ]);
        foreach($rowsBody as $row){
            $stockAdd = [
                'reception_id'  => $row->row,
                'product_id'    => $row->product_id,
                'supplier_id'   => $receptionHeader->supplier_id,
                'fabrication'   => $row->fabrication,
                'validity'      => $row->validity,
                'batch'         => $row->batch,
                'status'        => 'to store',
                'quantity'      => $row->quantity,
                'theoretical'   => $row->theoretical,
                'immobilized'   => false,
                'immob_code'    => null,
                'warehouse'     => substr($row->structure->id, 0, 1),
                'hall'          => substr($row->structure->id, 1, 6),
                'position'      => substr($row->structure->id, 7, 6),
                'level'         => substr($row->structure->id, 13, 4)
            ];
            $stock = M\Stock::create($stockAdd);
            $rowsBodyUd = M\ReceptionBody::findOrFail($row->row);
            $rowsBodyUd->update(['stock_id' => $stock->id]);
            M\Journal::create([
                'action'        => 'INS',
                'code'          => 'REC',
                'moreless'      => '+',
                'user_id'       => $row->user_id,
                'stock_id'      => $stock->id,
                'product_id'    => $stock->product_id,
                'fabrication'   => $stock->fabrication,
                'validity'      => $stock->validity,
                'batch'         => $stock->batch,
                'warehouse'     => $stock->warehouse,
                'hall'          => $stock->hall,
                'position'      => $stock->position,
                'level'         => $stock->level,
                'immobilized'   => $stock->immobilized,
                'immob_code'    => $stock->immob_code,
            ]);
        }
        session()->flash('message', 'Reception realized successfully.');
        $this->resetFields();
    }

    // public function confirmDeletion($id)
    // {
    //     $this->receptionHeaderToDelete = $id;
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
        $this->supplier_id = '';
        $this->xml_nf_header_id = '';
        $this->status = 'to receive';
        $this->enabled = true;
        $this->screenAction = 'table';
    }

    public function showTable()
    {
        $this->screenAction = 'table';
    }

    public function changeStatus($obRecHead, $statusTo)
    {
        switch ($statusTo):
            case 'to receive':
                if(in_array($obRecHead->status,['reception in progress','on hold'])){
                    $this->updateStatusToReceive($obRecHead->id);
                    return;
                }
                break;
            case 'reception in progress':
                if(in_array($obRecHead->status,['on hold'])){
                    $this->updateStatusReceptionInProgress($obRecHead->id);
                    return;
                }
                break;
            case 'received':
                if(in_array($obRecHead->status,['reception in progress'])){
                    $this->updateStatusReceived($obRecHead->id);
                    return;
                }
                break;
            case 'on hold':
                if(in_array($obRecHead->status,['to receive','reception in progress'])){
                    $this->updateStatusOnHold($obRecHead->id);
                    return;
                }
                break;
            case 'canceled':
                if(in_array($obRecHead->status,['to receive','reception in progress','on hold'])){
                    $this->updateStatusCanceled($obRecHead->id);
                    return;
                }
                break;
        endswitch;
        session()->flash('messageError', 'Change status is not allowed!');
    }

}
