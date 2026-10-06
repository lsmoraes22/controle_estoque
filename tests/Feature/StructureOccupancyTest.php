<?php

namespace Tests\Feature;

use App\Livewire\StructureManager;
use App\Models\Structure;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StructureOccupancy;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;

class StructureOccupancyTest extends DatabaseTestCase
{
    public function test_creation_cannot_forge_filled(): void
    {
        $this->actingAs(User::factory()->create());
        Warehouse::create(['warehouse' => 'Q', 'enabled' => true]);
        Livewire::test(StructureManager::class)->call('create')
            ->set('warehouse', 'Q')->set('hall', 1)->set('position', 1)->set('level', 1)
            ->set('filled', true)->call('store')->assertHasNoErrors();
        $structure = Structure::where('warehouse', 'Q')->sole();
        $this->assertFalse($structure->filled);
        $this->assertFalse(app(StructureOccupancy::class)->isOccupied($structure));
    }

    public function test_structure_locks_follow_the_same_id_order_for_inverse_requests(): void
    {
        Warehouse::create(['warehouse' => 'Q', 'enabled' => true]);
        foreach (['X', 'Y'] as $index => $id) {
            Structure::create(['id' => $id, 'warehouse' => 'Q', 'hall' => 1,
                'position' => $index + 1, 'level' => 1]);
        }
        $occupancy = app(StructureOccupancy::class);
        foreach ([['Y', 'X', 'Y', null], ['X', 'Y']] as $ids) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            try {
                $structures = $occupancy->lockStructures($ids);
                $locks = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'from `structures`') && str_contains($query['query'], 'for update'));
                $this->assertSame(['X', 'Y'], $structures->keys()->all());
                $this->assertSame(['X', 'Y'], $locks->map(fn ($query) => $query['bindings'][0])->values()->all());
            } finally {
                DB::disableQueryLog();
            }
        }
    }

    public function test_locked_recalculation_keeps_current_occupancy_reads_without_relocking_structure(): void
    {
        Warehouse::create(['warehouse' => 'Q', 'enabled' => true]);
        $structure = Structure::create(['id' => 'Q-lock', 'warehouse' => 'Q', 'hall' => 1,
            'position' => 1, 'level' => 1, 'filled' => true]);
        $occupancy = app(StructureOccupancy::class);
        $structures = $occupancy->lockStructures([$structure->id]);
        $occupancy->lockOccupants($structures);
        $locked = $structures->get($structure->id);
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $this->assertFalse($occupancy->recalculateLocked($locked));
            $queries = collect(DB::getQueryLog())->pluck('query');
            $this->assertFalse($queries->contains(fn ($sql) => str_contains($sql, 'from `structures`')));
            $this->assertTrue($queries->contains(fn ($sql) => str_contains($sql, 'from `stock`') && str_contains($sql, 'for update')));
            $this->assertTrue($queries->contains(fn ($sql) => str_contains($sql, 'from `reception_body`') && str_contains($sql, 'for update')));
        } finally {
            DB::disableQueryLog();
        }
        $this->assertFalse($structure->fresh()->filled);
    }

    public function test_standalone_recalculation_follows_global_hierarchy(): void
    {
        Warehouse::create(['warehouse' => 'Q', 'enabled' => true]);
        $structure = Structure::create(['id' => 'Q-order', 'warehouse' => 'Q', 'hall' => 1,
            'position' => 1, 'level' => 1]);
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            app(StructureOccupancy::class)->recalculate($structure);
            $locks = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'for update'))->values();
            foreach (['warehouses', 'structures', 'reception_body', 'stock'] as $i => $table) {
                $this->assertStringContainsString($table, $locks[$i]['query']);
            }
        } finally {
            DB::disableQueryLog();
        }
    }

    public static function filledStates(): array
    {
        return [[false, true], [true, false]];
    }

    #[DataProvider('filledStates')]
    public function test_edit_cannot_overwrite_filled(bool $persisted, bool $submitted): void
    {
        $this->actingAs(User::factory()->create());
        Warehouse::create(['warehouse' => 'Q', 'enabled' => true]);
        $structure = Structure::create(['id' => 'Q-test', 'warehouse' => 'Q',
            'hall' => 1, 'position' => 1, 'level' => 1, 'filled' => $persisted]);
        Livewire::test(StructureManager::class)->call('edit', $structure->id)
            ->assertDontSeeHtml('wire:model="filled"')->set('filled', $submitted)
            ->set('enabled', false)->call('update')->assertHasNoErrors();
        $this->assertSame($persisted, $structure->fresh()->filled);
        $this->assertFalse($structure->fresh()->enabled);
    }
}
