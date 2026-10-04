<?php

namespace App\Services;

use App\Models\ReceptionBody;
use App\Models\Stock;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class StructureOccupancy
{
    public function isOccupied(Structure $structure, ?int $exceptReceptionRow = null): bool
    {
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
     * Call before acquiring any Structure locks for the operation. The caller
     * owns the transaction and retains all returned locks until it completes.
     * Missing structures are omitted so callers can apply their domain rules.
     */
    public function lockStructures(array $ids): Collection
    {
        $this->assertTransaction();
        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id !== null)));
        sort($ids, SORT_STRING);
        $structures = new Collection;
        foreach ($ids as $id) {
            // Individual reads guarantee acquisition order independently of the query planner.
            $structure = Structure::query()->whereKey($id)->lockForUpdate()->first();
            if ($structure) {
                $structures->put($structure->id, $structure);
            }
        }

        return $structures;
    }

    public function recalculate(Structure $structure, ?int $exceptReceptionRow = null): bool
    {
        return DB::transaction(function () use ($structure, $exceptReceptionRow) {
            $locked = Structure::query()->whereKey($structure->id)->lockForUpdate()->firstOrFail();
            $occupied = $this->recalculateLocked($locked, $exceptReceptionRow);
            $structure->filled = $occupied;

            return $occupied;
        });
    }

    /**
     * The caller must already hold this Structure's FOR UPDATE lock in its
     * active transaction, e.g. from lockStructures(). No Structure is re-locked.
     */
    public function recalculateLocked(Structure $structure, ?int $exceptReceptionRow = null): bool
    {
        $this->assertTransaction();
        $occupied = $this->isOccupied($structure, $exceptReceptionRow);
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
