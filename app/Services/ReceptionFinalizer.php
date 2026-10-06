<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\ReceptionBody;
use App\Models\ReceptionHeader;
use App\Models\Stock;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceptionFinalizer
{
    public function finalize(int $headerId): void
    {
        DB::transaction(function () use ($headerId) {
            $occupancy = app(StructureOccupancy::class);
            $discovered = ReceptionBody::where('header', $headerId)->get();
            $structures = $occupancy->lockStructures($discovered->pluck('structure_id')->all());
            $header = ReceptionHeader::query()->lockForUpdate()->findOrFail($headerId);
            $occupancy->lockOccupants($structures, $headerId);
            if (! $header->enabled || $header->status !== 'reception in progress') {
                throw ValidationException::withMessages(['header' => 'Only an enabled reception in progress may be finalized.']);
            }

            $bodies = ReceptionBody::where('header', $header->id)->orderBy('row')->lockForUpdate()->get();
            if ($bodies->count() !== (int) $header->rows) {
                throw ValidationException::withMessages(['header' => 'Reception item count does not match the header.']);
            }

            foreach ($bodies as $body) {
                if (! $body->received || $body->quantity === null || $body->user_id === null
                    || $body->structure_id === null || BigDecimal::of($body->quantity)->compareTo('0') < 0) {
                    throw ValidationException::withMessages(['header' => 'Every item must be physically confirmed.']);
                }
                if ($body->stock_id !== null || Stock::where('reception_id', $body->row)->exists()) {
                    throw ValidationException::withMessages(['header' => 'An item already has stock.']);
                }
            }

            foreach ($bodies as $body) {
                if (! $structures->has($body->structure_id)) {
                    throw ValidationException::withMessages(['header' => 'Reception structures changed during lock discovery.']);
                }
            }

            foreach ($bodies as $body) {
                if (BigDecimal::of($body->quantity)->compareTo('0') === 0) {
                    $structure = $structures->get($body->structure_id);
                    if ($structure) {
                        $occupancy->recalculateLocked($structure);
                    }

                    continue;
                }
                $structure = $structures->get($body->structure_id);
                if (! $structure || ! $structure->enabled) {
                    throw ValidationException::withMessages(['header' => 'Every positive receipt requires an existing enabled structure.']);
                }
                $warehouse = $structure->warehouseModel()->lockForUpdate()->first();
                if (! $warehouse || ! $warehouse->enabled) {
                    throw ValidationException::withMessages(['header' => 'Every positive receipt requires an existing enabled warehouse.']);
                }
                $stock = Stock::create([
                    'reception_id' => $body->row,
                    'product_id' => $body->product_id,
                    'supplier_id' => $header->supplier_id,
                    'fabrication' => $body->fabrication,
                    'validity' => $body->validity,
                    'batch' => $body->batch,
                    'status' => 'to store',
                    'quantity' => $body->quantity,
                    'immobilized' => false,
                    'immob_code' => null,
                    'warehouse' => $structure->warehouse,
                    'hall' => $structure->hall,
                    'position' => $structure->position,
                    'level' => $structure->level,
                ]);
                $body->update(['stock_id' => $stock->id]);
                Journal::create([
                    'action' => 'INS', 'code' => 'REC', 'moreless' => '+',
                    'quantity' => $body->quantity,
                    'user_id' => $body->user_id, 'stock_id' => $stock->id,
                    'product_id' => $stock->product_id,
                    'fabrication' => $stock->fabrication, 'validity' => $stock->validity,
                    'batch' => $stock->batch,
                    'warehouse' => $stock->warehouse, 'hall' => $stock->hall,
                    'position' => $stock->position, 'level' => $stock->level,
                    'immobilized' => $stock->immobilized, 'immob_code' => $stock->immob_code,
                ]);
                $occupancy->recalculateLocked($structure);
            }
            $header->update(['status' => 'received']);
        });
    }
}
