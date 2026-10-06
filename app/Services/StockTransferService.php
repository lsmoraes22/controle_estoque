<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\Stock;
use App\Models\Structure;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function transfer(int $stockId, string $quantity, string $destinationStructureId, int $userId): Stock
    {
        return DB::transaction(function () use ($stockId, $quantity, $destinationStructureId, $userId) {
            $source = Stock::query()->whereKey($stockId)->first();
            if (! $source) {
                throw ValidationException::withMessages(['stock' => 'Source stock does not exist.']);
            }
            if (! preg_match('/^\d{1,16}(?:\.\d{1,4})?$/D', $quantity)) {
                throw ValidationException::withMessages(['quantity' => 'Use a positive DECIMAL(20,4) quantity.']);
            }
            $amount = BigDecimal::of($quantity)->toScale(4);

            $sourceStructure = Structure::query()
                ->where('warehouse', $source->warehouse)->where('hall', $source->hall)
                ->where('position', $source->position)->where('level', $source->level)->first();
            if (! $sourceStructure) {
                throw ValidationException::withMessages(['stock' => 'Source stock has no physical structure.']);
            }
            // Discovery is unlocked. All authoritative decisions use locked current rows.
            $occupancy = app(StructureOccupancy::class);
            $structures = $occupancy->lockStructures([$sourceStructure->id, $destinationStructureId]);
            $stocks = $occupancy->lockOccupants($structures, null, [], [$stockId, $source->origin_stock_id ?? $stockId]);
            $source = $stocks->get($stockId);
            if (! $source) {
                throw ValidationException::withMessages(['stock' => 'Source stock no longer exists.']);
            }
            $available = BigDecimal::of($source->quantity);
            if ($amount->compareTo('0') <= 0 || $amount->compareTo($available) > 0) {
                throw ValidationException::withMessages(['quantity' => 'Transfer quantity must be positive and no greater than available stock.']);
            }
            $sourceStructure = $structures->get($sourceStructure->id);
            $destination = $structures->get($destinationStructureId);
            if (! $destination || ! $destination->enabled) {
                throw ValidationException::withMessages(['destination' => 'Destination structure must exist and be enabled.']);
            }
            if (! $sourceStructure || ! $this->atLocation($source, $sourceStructure)) {
                throw ValidationException::withMessages(['stock' => 'Source structure location changed.']);
            }
            if ($this->atLocation($source, $destination)) {
                throw ValidationException::withMessages(['destination' => 'Destination must differ from source location.']);
            }
            $warehouse = $destination->warehouseModel()->lockForUpdate()->first();
            if (! $warehouse || ! $warehouse->enabled) {
                throw ValidationException::withMessages(['destination' => 'Destination warehouse must exist and be enabled.']);
            }
            if (! $warehouse->multiple && $occupancy->isOccupiedLocked($destination)) {
                throw ValidationException::withMessages(['destination' => 'Destination is occupied.']);
            }

            // Capture the outbound location before any Stock mutation.
            $this->journal($source, (string) $amount, $userId, '-', 'UPD');
            $location = $destination->only(['warehouse', 'hall', 'position', 'level']);
            if ($amount->compareTo($available) === 0) {
                $source->update($location);
                $moved = $source;
                $inboundAction = 'UPD';
            } else {
                $source->update(['quantity' => (string) $available->minus($amount)->toScale(4)]);
                $moved = Stock::create(array_merge(
                    $source->only(['product_id', 'supplier_id', 'fabrication', 'validity', 'batch', 'immobilized', 'immob_code', 'status']),
                    $location,
                    ['reception_id' => null, 'origin_stock_id' => $source->origin_stock_id ?? $source->id,
                        'quantity' => (string) $amount],
                ));
                $inboundAction = 'INS';
            }
            // Actions describe actual mutations: source UPD; new split stock INS.
            $this->journal($moved, (string) $amount, $userId, '+', $inboundAction);
            $occupancy->recalculateLocked($sourceStructure);
            $occupancy->recalculateLocked($destination);

            return $moved;
        });
    }

    private function atLocation(Stock $stock, Structure $structure): bool
    {
        return $stock->warehouse === $structure->warehouse
            && (int) $stock->hall === (int) $structure->hall
            && (int) $stock->position === (int) $structure->position
            && (int) $stock->level === (int) $structure->level;
    }

    private function journal(Stock $stock, string $quantity, int $userId, string $direction, string $action): void
    {
        Journal::create(array_merge(
            $stock->only(['product_id', 'fabrication', 'validity', 'batch', 'warehouse', 'hall', 'position', 'level', 'immobilized', 'immob_code']),
            ['stock_id' => $stock->id, 'quantity' => $quantity, 'user_id' => $userId,
                'code' => 'TRF', 'moreless' => $direction, 'action' => $action],
        ));
    }
}
