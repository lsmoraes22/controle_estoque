<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\Product;
use App\Models\ReceptionBody;
use App\Models\ReceptionHeader;
use App\Models\Stock;
use App\Models\Structure;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\XmlNfHeader;
use App\Services\StockTransferService;
use App\Services\StructureOccupancy;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\DatabaseTestCase;

class StockTransferTest extends DatabaseTestCase
{
    private Stock $source;

    private Structure $from;

    private Structure $to;

    private User $operator;

    private ReceptionBody $body;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operator = User::factory()->create();
        $supplier = Supplier::create(['supplier' => 'Transfer test', 'address' => 'Test', 'phone1' => '00000000000',
            'email' => 'transfer@example.test', 'cnpj' => '00000000000000']);
        Product::create(['id' => 'TRANSFER-1', 'description' => 'Transfer fixture', 'category_id' => 1]);
        $xml = XmlNfHeader::create(['tipoNF' => 'compra', 'idnf' => 'TRANSFER']);
        $header = ReceptionHeader::create(['supplier_id' => $supplier->id, 'xml_nf_header_id' => $xml->id,
            'rows' => 1, 'status' => 'received']);
        foreach (['A', 'B'] as $code) {
            Warehouse::create(['warehouse' => $code, 'enabled' => true, 'multiple' => false]);
        }
        // Source ID sorts after destination: input direction must not control lock order.
        $this->from = Structure::create(['id' => 'Z-source', 'warehouse' => 'A', 'hall' => 1, 'position' => 2, 'level' => 3]);
        $this->to = Structure::create(['id' => 'A-destination', 'warehouse' => 'B', 'hall' => 4, 'position' => 5, 'level' => 6]);
        $this->body = ReceptionBody::create(['header' => $header->id, 'product_id' => 'TRANSFER-1',
            'quantity' => '10.5000', 'received' => true, 'structure_id' => $this->from->id, 'user_id' => $this->operator->id]);
        $this->source = Stock::create(['reception_id' => $this->body->row, 'product_id' => 'TRANSFER-1',
            'supplier_id' => $supplier->id, 'fabrication' => '2026-09-01', 'validity' => '2027-09-01',
            'batch' => 'LOT-X', 'immobilized' => false, 'status' => 'stored', 'quantity' => '10.5000',
            'warehouse' => 'A', 'hall' => 1, 'position' => 2, 'level' => 3]);
        $this->body->update(['stock_id' => $this->source->id]);
        app(StructureOccupancy::class)->recalculate($this->from);
    }

    private function transfer(string $quantity = '10.5000', ?int $sourceId = null, ?string $destination = null): Stock
    {
        return app(StockTransferService::class)->transfer($sourceId ?? $this->source->id, $quantity,
            $destination ?? $this->to->id, $this->operator->id);
    }

    public function test_total_moves_same_stock_preserving_traceability_and_exact_journal_snapshots(): void
    {
        $before = $this->source->fresh()->getAttributes();
        $moved = $this->transfer();
        $this->assertSame($this->source->id, $moved->id);
        $this->assertDatabaseCount('stock', 1);
        $this->assertSame($this->body->row, $moved->reception_id);
        $this->assertSame($moved->id, $this->body->fresh()->stock_id);
        foreach (['product_id', 'supplier_id', 'fabrication', 'validity', 'batch', 'immobilized', 'immob_code', 'status', 'quantity', 'origin_stock_id'] as $field) {
            $this->assertEquals($before[$field], $moved->getRawOriginal($field));
        }
        $this->assertLocation($moved, $this->to);
        $this->assertJournals($this->source->id, $moved->id, '10.5000', 'UPD');
        $this->assertFalse($this->from->fresh()->filled);
        $this->assertTrue($this->to->fresh()->filled);
    }

    public function test_partial_preserves_root_link_and_reduces_exactly_with_new_stock_and_journals(): void
    {
        $moved = $this->transfer('0.1234');
        $this->assertNotSame($this->source->id, $moved->id);
        $this->assertSame('10.3766', $this->source->fresh()->quantity);
        $this->assertSame('0.1234', $moved->quantity);
        $this->assertNull($moved->reception_id);
        $this->assertSame($this->source->id, $moved->origin_stock_id);
        $this->assertTrue($moved->originStock->is($this->source));
        $this->assertNull($moved->receptionBody);
        $this->assertSame($this->source->id, $this->body->fresh()->stock_id);
        foreach (['product_id', 'supplier_id', 'fabrication', 'validity', 'batch', 'immobilized', 'immob_code', 'status'] as $field) {
            $this->assertEquals($this->source->$field, $moved->$field);
        }
        $this->assertJournals($this->source->id, $moved->id, '0.1234', 'INS');
        $this->assertTrue($this->from->fresh()->filled);
        $this->assertTrue($this->to->fresh()->filled);
    }

    public function test_splitting_derived_stock_links_directly_to_root(): void
    {
        $child = $this->transfer('2.5000');
        $third = Structure::create(['id' => 'C-third', 'warehouse' => 'B', 'hall' => 4, 'position' => 7, 'level' => 6]);
        $grandchild = $this->transfer('0.0001', $child->id, $third->id);
        $this->assertSame($this->source->id, $grandchild->origin_stock_id);
        $this->assertNull($grandchild->reception_id);
        $this->assertSame('2.4999', $child->fresh()->quantity);
        $this->assertSame('0.0001', $grandchild->quantity);
        $this->assertSame($this->source->id, $this->body->fresh()->stock_id);
        $this->assertDatabaseCount('stock', 3);
        $this->assertDatabaseCount('journals', 4);
    }

    public function test_transfer_preserves_existing_reception_journal_snapshot(): void
    {
        $receipt = Journal::create(array_merge(
            $this->source->only(['product_id', 'fabrication', 'validity', 'batch', 'warehouse', 'hall', 'position', 'level', 'immobilized', 'immob_code']),
            ['stock_id' => $this->source->id, 'quantity' => '10.5000', 'user_id' => $this->operator->id,
                'code' => 'REC', 'action' => 'INS', 'moreless' => '+'],
        ));
        $before = $receipt->fresh()->getAttributes();
        $this->transfer();
        $this->assertSame($before, $receipt->fresh()->getAttributes());
        $this->assertSame(2, Journal::where('code', 'TRF')->count());
        $this->assertSame(1, Journal::where('code', 'REC')->count());
    }

    public function test_partial_preserves_immobilization_fields_in_stock_and_journal(): void
    {
        DB::table('immobilizations')->insert(['immob_code' => 'TST', 'immob_reason' => 'Test reason']);
        $this->source->update(['immobilized' => true, 'immob_code' => 'TST']);
        $moved = $this->transfer('1.0000');
        $this->assertEquals(1, $moved->immobilized);
        $this->assertSame('TST', $moved->immob_code);
        foreach (Journal::where('code', 'TRF')->get() as $journal) {
            $this->assertEquals(1, $journal->immobilized);
            $this->assertSame('TST', $journal->immob_code);
        }
    }

    public function test_zero_pending_receipt_does_not_block_destination(): void
    {
        ReceptionBody::create(['header' => $this->body->header, 'product_id' => $this->source->product_id,
            'structure_id' => $this->to->id, 'received' => true, 'quantity' => '0.0000']);
        $this->transfer();
        $this->assertTrue($this->to->fresh()->filled);
        $this->assertDatabaseCount('stock', 1);
        $this->assertDatabaseCount('journals', 2);
    }

    public function test_large_decimal_subtraction_never_uses_float(): void
    {
        $this->source->update(['quantity' => '9999999999999999.9999']);
        $moved = $this->transfer('1234567890123456.1234');
        $this->assertSame('8765432109876543.8765', $this->source->fresh()->quantity);
        $this->assertSame('1234567890123456.1234', $moved->quantity);
        $this->assertSame(['1234567890123456.1234', '1234567890123456.1234'], Journal::orderBy('id')->pluck('quantity')->all());
    }

    public static function invalidQuantities(): array
    {
        return [['0'], ['0.0000'], ['-0.0001'], ['10.5001'], ['1.00001'], ['10000000000000000'], ['1e0'], ['abc']];
    }

    #[DataProvider('invalidQuantities')]
    public function test_invalid_quantity_is_rejected_atomically(string $quantity): void
    {
        $this->assertRejected(fn () => $this->transfer($quantity), 'quantity');
    }

    public function test_missing_stock_is_rejected(): void
    {
        $this->assertRejected(fn () => $this->transfer('1', $this->source->id + 1000), 'stock');
    }

    public function test_same_location_is_rejected(): void
    {
        $this->assertRejected(fn () => $this->transfer('1', null, $this->from->id), 'destination');
    }

    public function test_missing_destination_is_rejected(): void
    {
        $this->assertRejected(fn () => $this->transfer('1', null, 'missing'), 'destination');
    }

    public function test_disabled_destination_structure_is_rejected(): void
    {
        $this->to->update(['enabled' => false]);
        $this->assertRejected(fn () => $this->transfer('1'), 'destination');
    }

    public function test_disabled_destination_warehouse_is_rejected(): void
    {
        $this->to->warehouseModel()->update(['enabled' => false]);
        $this->assertRejected(fn () => $this->transfer('1'), 'destination');
    }

    public function test_occupied_single_destination_is_rejected(): void
    {
        $this->destinationStock();
        $this->assertRejected(fn () => $this->transfer('1'), 'destination');
    }

    public function test_positive_pending_receipt_blocks_single_destination(): void
    {
        ReceptionBody::create(['header' => $this->body->header, 'product_id' => $this->source->product_id,
            'structure_id' => $this->to->id, 'received' => true, 'quantity' => '0.0001']);
        $this->assertRejected(fn () => $this->transfer('1'), 'destination');
    }

    public function test_sharing_destination_allows_split_without_consolidation(): void
    {
        $existing = $this->destinationStock();
        $this->to->warehouseModel()->update(['multiple' => true]);
        $moved = $this->transfer('1.2500');
        $this->assertNotSame($existing->id, $moved->id);
        $this->assertSame('3.0000', $existing->fresh()->quantity);
        $this->assertSame('1.2500', $moved->quantity);
        $this->assertSame(2, Stock::where('warehouse', 'B')->count());
        $this->assertDatabaseCount('stock', 3);
        $this->assertDatabaseCount('journals', 2);
    }

    public function test_total_preserves_source_occupancy_from_another_stock(): void
    {
        Stock::create(array_merge($this->source->only(['product_id', 'quantity', 'warehouse', 'hall', 'position', 'level', 'status', 'immobilized']),
            ['reception_id' => null, 'origin_stock_id' => $this->source->id]));
        $this->transfer();
        $this->assertTrue($this->from->fresh()->filled);
    }

    public function test_total_preserves_source_occupancy_from_pending_receipt(): void
    {
        ReceptionBody::create(['header' => $this->body->header, 'product_id' => $this->source->product_id,
            'structure_id' => $this->from->id, 'received' => true, 'quantity' => '0.0001']);
        $this->transfer();
        $this->assertTrue($this->from->fresh()->filled);
    }

    public function test_locks_warehouses_then_canonical_structures_then_bodies_then_stocks(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $this->transfer('1');
            $locks = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'for update'))->values();
            $this->assertStringContainsString('from `warehouses`', $locks[0]['query']);
            $firstBody = $locks->search(fn ($q) => str_contains($q['query'], 'reception_body'));
            $firstStock = $locks->search(fn ($q) => str_contains($q['query'], 'stock'));
            $lastStructure = $locks->filter(fn ($q) => str_contains($q['query'], 'from `structures`'))->keys()->last();
            $this->assertTrue($lastStructure < $firstBody);
            $this->assertTrue($firstBody < $firstStock);
            $structures = $locks->filter(fn ($q) => str_contains($q['query'], 'from `structures`'));
            $this->assertSame(['A-destination', 'Z-source'], $structures->map(fn ($q) => $q['bindings'][0])->values()->all());
        } finally {
            DB::disableQueryLog();
        }
        // Sequential SQL inspection verifies policy, not freedom from concurrent deadlocks.
    }

    public function test_transfer_revalidates_current_quantity_after_unlocked_discovery(): void
    {
        $dispatcher = Stock::getEventDispatcher();
        Stock::setEventDispatcher(clone $dispatcher);
        $changed = false;
        Stock::retrieved(function ($stock) use (&$changed) {
            if (! $changed && $stock->id === $this->source->id) {
                $changed = true;
                DB::table('stock')->where('id', $stock->id)->update(['quantity' => '1.0000']);
            }
        });
        try {
            $this->assertRejected(fn () => $this->transfer('2.0000'), 'quantity');
        } finally {
            Stock::setEventDispatcher($dispatcher);
        }
        $this->assertTrue($changed);
    }

    public function test_transfer_rejects_changed_source_location_without_expanding_lock_set(): void
    {
        $dispatcher = Stock::getEventDispatcher();
        Stock::setEventDispatcher(clone $dispatcher);
        $changed = false;
        Stock::retrieved(function ($stock) use (&$changed) {
            if (! $changed && $stock->id === $this->source->id) {
                $changed = true;
                DB::table('stock')->where('id', $stock->id)->update($this->to->only(['warehouse', 'hall', 'position', 'level']));
            }
        });
        try {
            $this->assertRejected(fn () => $this->transfer('1.0000'), 'stock');
        } finally {
            Stock::setEventDispatcher($dispatcher);
        }
        $this->assertTrue($changed);
    }

    public static function transferModes(): array
    {
        return [['10.5000'], ['1.2500']];
    }

    #[DataProvider('transferModes')]
    public function test_failure_after_occupancy_updates_rolls_back_entire_transfer(string $quantity): void
    {
        $before = $this->snapshot();
        $dispatcher = Structure::getEventDispatcher();
        Structure::setEventDispatcher(clone $dispatcher);
        Structure::updated(function ($structure) {
            if ($structure->id === $this->to->id) {
                $this->assertTrue($this->to->fresh()->filled);
                $this->assertDatabaseCount('journals', 2);
                throw new RuntimeException('Forced transfer failure');
            }
        });
        try {
            try {
                $this->transfer($quantity);
                $this->fail('Expected forced failure');
            } catch (RuntimeException $e) {
                $this->assertSame('Forced transfer failure', $e->getMessage());
            }
        } finally {
            Structure::setEventDispatcher($dispatcher);
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_schema_preserves_unique_roots_nullable_reception_and_restricted_lineage(): void
    {
        $columns = collect(Schema::getColumns('stock'));
        $this->assertTrue($columns->firstWhere('name', 'reception_id')['nullable']);
        $this->assertTrue($columns->firstWhere('name', 'origin_stock_id')['nullable']);
        $keys = collect(Schema::getForeignKeys('stock'));
        $this->assertSame('reception_body', $keys->firstWhere('columns', ['reception_id'])['foreign_table']);
        $origin = $keys->firstWhere('columns', ['origin_stock_id']);
        $this->assertSame('stock', $origin['foreign_table']);
        $this->assertSame('restrict', strtolower($origin['on_delete']));
        $child = $this->transfer('1');
        $this->assertConstraintRejects(fn () => Stock::create($this->source->getAttributes()), 1062);
        $this->assertConstraintRejects(fn () => $child->update(['origin_stock_id' => 999999]), 1452);
        $this->assertConstraintRejects(fn () => $this->source->delete(), 1451);
        $this->assertSame($this->source->id, $child->fresh()->origin_stock_id);
        $this->assertConstraintRejects(fn () => $child->update(['reception_id' => 999999]), 1452);
    }

    public function test_migration_down_refuses_to_discard_derived_traceability_before_ddl(): void
    {
        $this->transfer('1');
        $migration = require database_path('migrations/2026_10_04_000002_add_stock_lineage.php');
        try {
            $migration->down();
            $this->fail('Expected guarded rollback');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('derived stocks', $e->getMessage());
        }
        $this->assertTrue(Schema::hasColumn('stock', 'origin_stock_id'));
        $this->assertDatabaseCount('stock', 2);
    }

    private function destinationStock(): Stock
    {
        return Stock::create(array_merge($this->source->only(['product_id', 'supplier_id', 'fabrication', 'validity', 'batch', 'status', 'immobilized', 'immob_code']),
            $this->to->only(['warehouse', 'hall', 'position', 'level']),
            ['quantity' => '3.0000', 'reception_id' => null, 'origin_stock_id' => $this->source->id]));
    }

    private function assertJournals(int $outId, int $inId, string $quantity, string $inAction): void
    {
        $this->assertDatabaseCount('journals', 2);
        $journals = Journal::orderBy('id')->get();
        foreach ([[$outId, $this->from, '-', 'UPD'], [$inId, $this->to, '+', $inAction]] as $i => [$id, $structure, $direction, $action]) {
            $journal = $journals[$i];
            $this->assertSame('TRF', $journal->code);
            $this->assertSame($id, $journal->stock_id);
            $this->assertSame($direction, $journal->moreless);
            $this->assertSame($action, $journal->action);
            $this->assertSame($quantity, $journal->quantity);
            $this->assertSame($this->operator->id, $journal->user_id);
            $this->assertLocation($journal, $structure);
            foreach (['product_id', 'fabrication', 'validity', 'batch', 'immobilized', 'immob_code'] as $field) {
                $this->assertEquals($this->source->$field, $journal->$field);
            }
        }
    }

    private function assertLocation($model, Structure $structure): void
    {
        foreach (['warehouse', 'hall', 'position', 'level'] as $field) {
            $this->assertEquals($structure->$field, $model->$field);
        }
    }

    private function snapshot(): array
    {
        return collect(['stock', 'journals', 'structures', 'reception_body'])->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy($table === 'reception_body' ? 'row' : 'id')->get()->toJson()])->all();
    }

    private function assertRejected(callable $operation, string $field): void
    {
        $before = $this->snapshot();
        try {
            $operation();
            $this->fail('Expected rejection');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
        }
        $this->assertSame($before, $this->snapshot());
    }

    private function assertConstraintRejects(callable $operation, int $code): void
    {
        try {
            $operation();
            $this->fail('Expected constraint violation');
        } catch (QueryException $e) {
            $this->assertSame($code, $e->errorInfo[1]);
        }
    }
}
