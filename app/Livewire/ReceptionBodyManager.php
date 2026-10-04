<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models as M;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        'batch' => 'nullable|string|max:30',
        'quantity' => ['required', 'numeric', 'min:0', 'regex:/^\\d{1,16}(?:\\.\\d{1,4})?$/'],
        'structure_id' => 'required|string|exists:structures,id',
        'fabrication' => 'nullable|date_format:Y-m-d',
        'validity' => 'nullable|date_format:Y-m-d',
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
                'type'  => 'date',
                'label' => 'Fabrication',
            ],
            'validity' => [
                'model' => 'validity',
                'type'  => 'date',
                'label' => 'Validity',
            ],
            'structure_id' => [
                'model' => 'structure_id',
                'type'  => 'select',
                'label' => 'Structure',
                'options' => array_merge([
                    ['value' => '', 'caption' => 'Select structure...'],
                ], M\Structure::query()
                    ->where('enabled', true)
                    ->whereHas('warehouseModel', fn ($query) => $query->where('enabled', true))
                    ->orderBy('warehouse')
                    ->orderBy('hall')
                    ->orderBy('position')
                    ->orderBy('level')
                    ->get()
                    ->map(fn (M\Structure $structure) => [
                        'value' => $structure->id,
                        'caption' => "{$structure->warehouse} / {$structure->hall} / {$structure->position} / {$structure->level}",
                    ])->all()),
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
        $this->assertHeaderEditable($receptionBody->ReceptionHeader);
        $this->id = $receptionBody->row;
        $this->header = $receptionBody->header; 
        $this->batch = $receptionBody->batch;
        $this->quantity = $receptionBody->quantity;
        $this->fabrication = $receptionBody->fabrication;
        $this->validity = $receptionBody->validity;
        $this->structure_id = $receptionBody->structure_id;
        $this->received = (bool) $receptionBody->received;
        $this->status = $receptionBody->received ? 'received' : 'to receive';
        $this->screenAction = 'edit';
    }

    public function update()
    {
        $receptionBody = M\ReceptionBody::query()
            ->where('row', $this->id)
            ->where('header', $this->header)
            ->firstOrFail();
        $receptionHeader = $receptionBody->ReceptionHeader;
        $this->assertHeaderEditable($receptionHeader);

        if (!auth()->check()) {
            throw ValidationException::withMessages(['user_id' => 'An authenticated user is required.']);
        }

        if (!$receptionBody->received) {
            $validated = $this->validate();
            $structure = M\Structure::query()->findOrFail($validated['structure_id']);
            if (!$structure->enabled) {
                throw ValidationException::withMessages(['structure_id' => 'Selected structure is disabled.']);
            }
            $warehouse = M\Warehouse::query()->findOrFail($structure->warehouse);
            if (!$warehouse->enabled) {
                throw ValidationException::withMessages(['structure_id' => 'Selected warehouse is disabled.']);
            }

            DB::transaction(function () use ($receptionBody, $receptionHeader, $structure, $warehouse, $validated): void {
                $structure = M\Structure::query()->whereKey($structure->id)->lockForUpdate()->firstOrFail();
                if (!$warehouse->multiple && ($structure->filled || $this->hasOtherOccupancy($structure, $receptionBody->row))) {
                    throw ValidationException::withMessages(['structure_id' => 'This position is already occupied.']);
                }

                $oldStructure = $receptionBody->structure_id
                    ? M\Structure::query()->whereKey($receptionBody->structure_id)->lockForUpdate()->first()
                    : null;
                $receptionBody->update([
                    'batch' => $validated['batch'] ?? null,
                    'quantity' => $validated['quantity'],
                    'fabrication' => $validated['fabrication'] ?? null,
                    'validity' => $validated['validity'] ?? null,
                    'structure_id' => $structure->id,
                    'user_id' => auth()->id(),
                    'received' => true,
                ]);
                $structure->update(['filled' => true]);
                if ($oldStructure && $oldStructure->isNot($structure)) {
                    $oldStructure->update(['filled' => $this->hasOtherOccupancy($oldStructure, $receptionBody->row)]);
                }
                $receptionHeader->update(['status' => 'reception in progress']);
            });
        } else {
            if ($this->received) {
                return;
            }

            DB::transaction(function () use ($receptionBody): void {
                $receptionBody = M\ReceptionBody::query()->whereKey($receptionBody->row)->lockForUpdate()->firstOrFail();
                $oldStructure = $receptionBody->structure_id
                    ? M\Structure::query()->whereKey($receptionBody->structure_id)->lockForUpdate()->first()
                    : null;
                $receptionBody->update(['received' => false]);
                if ($oldStructure) {
                    $oldStructure->update(['filled' => $this->hasOtherOccupancy($oldStructure, $receptionBody->row)]);
                }
            });
        }

        $this->screenAction = 'table';
        $this->received = (bool) $receptionBody->fresh()->received;
        $this->status = $this->received ? 'received' : 'to receive';
    }

    private function assertHeaderEditable(?M\ReceptionHeader $header): void
    {
        if (!$header || !$header->enabled || in_array($header->status, ['received', 'canceled', 'on hold'], true)) {
            throw ValidationException::withMessages(['header' => 'This reception cannot be edited in its current state.']);
        }
    }

    private function hasOtherOccupancy(M\Structure $structure, int $exceptReceptionRow): bool
    {
        $pendingReceptionOccupancy = M\ReceptionBody::query()
            ->where('structure_id', $structure->id)
            ->where('received', true)
            ->where('row', '!=', $exceptReceptionRow)
            ->exists();

        return $pendingReceptionOccupancy || M\Stock::query()
            ->where('warehouse', $structure->warehouse)
            ->where('hall', $structure->hall)
            ->where('position', $structure->position)
            ->where('level', $structure->level)
            ->exists();
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