<?php

namespace App\Services;

use App\Models\ReceptionBody;
use App\Models\Stock;
use App\Models\Structure;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class StructureOccupancy
{
    /** Standalone entry point: call before holding lower-ranked resource locks. */
    public function isOccupied(Structure $structure, ?int $exceptReceptionRow = null): bool
    {
        return DB::transaction(function () use ($structure, $exceptReceptionRow) {
            $structures = $this->lockStructures([$structure->id]);
            $locked = $structures->get($structure->id);
            if (! $locked) {
                throw new LogicException('Structure no longer exists.');
            }
            $this->lockOccupants($structures);

            return $this->isOccupiedLocked($locked, $exceptReceptionRow);
        });
    }

    /** Caller owns Warehouse/Structure and all occupant locks from lockOccupants(). */
    public function isOccupiedLocked(Structure $structure, ?int $exceptReceptionRow = null): bool
    {
        $this->assertTransaction();
        $stock = Stock::query()
            ->where('warehouse', $structure->warehouse)
            ->where('hall', $structure->hall)
            ->where('position', $structure->position)
            ->where('level', $structure->level);
        $pending = ReceptionBody::query()
            ->where('structure_id', $structure->id)
            ->where('received', true)
            ->whereNull('stock_id')
                // MariaDB compares the DECIMAL column exactly; no PHP float conversion.
            ->where('quantity', '>', '0.0000')
            ->when($exceptReceptionRow !== null, fn ($query) => $query->where('row', '!=', $exceptReceptionRow));

        // Current locking reads avoid a stale repeatable-read snapshot after waiting for a structure lock.
        if (DB::transactionLevel() > 0) {
            return $stock->lockForUpdate()->first() !== null || $pending->lockForUpdate()->first() !== null;
        }

        return $stock->exists() || $pending->exists();
    }

    /**
     * Global hierarchy: Warehouse IDs -> Structure IDs -> optional target Header
     * -> Body rows -> Stock rows. Call before acquiring any lower-ranked locks.
     * The caller
     * owns the transaction and retains all returned locks until it completes.
     * Missing structures are omitted so callers can apply their domain rules.
     */
    public function lockStructures(array $ids): Collection
    {
        $this->assertTransaction();
        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id !== null)));
        sort($ids, SORT_STRING);
        $discovered = Structure::query()->whereIn('id', $ids)->get();
        $warehouseIds = $discovered->pluck('warehouse')->unique()->all();
        sort($warehouseIds, SORT_STRING);
        foreach ($warehouseIds as $warehouseId) {
            Warehouse::query()->whereKey($warehouseId)->lockForUpdate()->first();
        }
        $structures = new Collection;
        foreach ($ids as $id) {
            // Individual reads guarantee acquisition order independently of the query planner.
            $structure = Structure::query()->whereKey($id)->lockForUpdate()->first();
            if ($structure) {
                if (! in_array($structure->warehouse, $warehouseIds, true)) {
                    throw new LogicException('Structure warehouse changed during lock discovery; start a new operation.');
                }
                $structures->put($structure->id, $structure);
            }
        }

        return $structures;
    }

    /**
     * After Warehouse/Structure and optional target Header locks, acquire ALL
     * relevant Body rows, then Stock rows. PRIMARY scans enforce row-ID order.
     * Includes unchecked bodies and stock roots for FK/lineage ownership.
     */
    public function lockOccupants(Collection $structures, ?int $headerId = null, array $bodyIds = [], array $stockIds = []): Collection
    {
        $this->assertTransaction();
        ReceptionBody::query()->from(DB::raw('reception_body FORCE INDEX (PRIMARY)'))
            ->where(function ($query) use ($structures, $headerId, $bodyIds) {
                $query->whereIn('structure_id', $structures->keys()->all())
                    ->orWhereIn('row', $bodyIds);
                if ($headerId !== null) {
                    $query->orWhere('header', $headerId);
                }
            })->orderBy('row')->lockForUpdate()->get();

        return Stock::query()->from(DB::raw('stock FORCE INDEX (PRIMARY)'))
            ->where(function ($query) use ($structures, $stockIds) {
                $query->whereIn('id', $stockIds);
                foreach ($structures as $structure) {
                    $query->orWhere(function ($location) use ($structure) {
                        $location->where('warehouse', $structure->warehouse)->where('hall', $structure->hall)
                            ->where('position', $structure->position)->where('level', $structure->level);
                    });
                }
            })->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    public function recalculate(Structure $structure, ?int $exceptReceptionRow = null): bool
    {
        return DB::transaction(function () use ($structure, $exceptReceptionRow) {
            $structures = $this->lockStructures([$structure->id]);
            $locked = $structures->get($structure->id);
            if (! $locked) {
                throw new LogicException('Structure no longer exists.');
            }
            $this->lockOccupants($structures);
            $occupied = $this->recalculateLocked($locked, $exceptReceptionRow);
            $structure->filled = $occupied;

            return $occupied;
        });
    }

    /**
     * The caller must already hold this Structure's FOR UPDATE lock in its
     * active transaction from lockStructures(), followed by lockOccupants().
     * No Structure is re-locked; occupancy locking reads only revisit owned rows.
     */
    public function recalculateLocked(Structure $structure, ?int $exceptReceptionRow = null): bool
    {
        $this->assertTransaction();
        $occupied = $this->isOccupiedLocked($structure, $exceptReceptionRow);
        $structure->update(['filled' => $occupied]);

        return $occupied;
    }

    private function assertTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Structure lock ownership requires an active transaction.');
        }
    }
}
