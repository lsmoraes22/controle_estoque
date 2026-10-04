<?php

namespace Tests\Feature;

use App\Livewire\ReceptionHeaderManager;
use App\Models\Journal;
use App\Models\ReceptionBody;
use App\Models\ReceptionHeader;
use App\Models\Stock;
use App\Models\Structure;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Nfe\NfeInboundImporter;
use App\Services\Nfe\NfeInboundParser;
use App\Services\ReceptionFinalizer;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\DatabaseTestCase;

class ReceptionFinalizationTest extends DatabaseTestCase
{
    private ReceptionHeader $header;

    private $bodies;

    private Structure $structure;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user);
        Supplier::create(['supplier' => 'Ficticio', 'address' => 'Ficticio', 'phone1' => '00000000000',
            'email' => 'finalization@example.test', 'cnpj' => '00000000000000']);
        $this->header = (new NfeInboundImporter())->import((new NfeInboundParser())->parse(
            file_get_contents(base_path('tests/Fixtures/nfe/inbound-ficticia.xml'))
        ))->receptionHeader;
        Warehouse::create(['warehouse' => 'Z', 'description' => 'Test', 'multiple' => true, 'enabled' => true]);
        $this->structure = Structure::create(['id' => 'opaque-address', 'warehouse' => 'Z',
            'hall' => 12, 'position' => 34, 'level' => 5, 'enabled' => true]);
        $this->header->update(['status' => 'reception in progress']);
        $this->bodies = ReceptionBody::where('header', $this->header->id)->orderBy('row')->get();
        foreach ($this->bodies as $body) {
            $body->update(['received' => true, 'quantity' => '1.2500', 'user_id' => $user->id,
                'structure_id' => $this->structure->id]);
        }
    }

    public function test_multi_item_finalization_links_stock_and_records_physical_movements_and_address(): void
    {
        $this->bodies[0]->update(['quantity' => '9999999999999999.1234']);
        $this->assertNotSame($this->bodies[1]->theoretical, $this->bodies[1]->quantity);
        $this->finalize();
        $this->assertSame('received', $this->header->fresh()->status);
        $this->assertTrue($this->structure->fresh()->filled);
        $this->assertDatabaseCount('stock', 2);
        $this->assertDatabaseCount('journals', 2);
        foreach ($this->bodies as $body) {
            $stock = Stock::where('reception_id', $body->row)->sole();
            $this->assertSame($stock->id, $body->fresh()->stock_id);
            $this->assertSame($body->fresh()->quantity, $stock->quantity);
            $journal = Journal::where('stock_id', $stock->id)->sole();
            $this->assertSame($stock->quantity, $journal->quantity);
            $this->assertSame('INS', $journal->action);
            $this->assertSame('REC', $journal->code);
            $this->assertSame('+', $journal->moreless);
            $this->assertSame($body->user_id, $journal->user_id);
            foreach (['warehouse', 'hall', 'position', 'level'] as $field) {
                $this->assertEquals($this->structure->$field, $stock->$field);
                $this->assertEquals($stock->$field, $journal->$field);
            }
        }
    }

    public function test_zero_quantity_creates_no_movement(): void
    {
        $this->bodies[0]->update(['quantity' => '0.0000']);
        $this->finalize();
        $this->assertNull($this->bodies[0]->fresh()->stock_id);
        $this->assertDatabaseCount('stock', 1);
        $this->assertDatabaseCount('journals', 1);
        $this->assertSame('received', $this->header->fresh()->status);
    }

    public function test_structure_disabled_after_conference_rolls_back_finalization(): void
    {
        $structure = $this->secondStructure();
        $structure->update(['enabled' => false]);
        $this->assertRejected();
    }

    public function test_warehouse_disabled_after_conference_rolls_back_finalization(): void
    {
        $structure = $this->secondStructure();
        $structure->warehouseModel()->update(['enabled' => false]);
        $this->assertRejected();
    }

    public function test_zero_quantity_does_not_require_stock_at_a_disabled_address(): void
    {
        $structure = $this->secondStructure();
        $this->bodies[1]->update(['quantity' => '0.0000']);
        $structure->update(['enabled' => false]);
        $structure->warehouseModel()->update(['enabled' => false]);
        $this->finalize();
        $this->assertSame('received', $this->header->fresh()->status);
        $this->assertNull($this->bodies[1]->fresh()->stock_id);
        $this->assertNotNull($this->bodies[0]->fresh()->stock_id);
        $this->assertDatabaseCount('stock', 1);
        $this->assertDatabaseCount('journals', 1);
    }

    private function secondStructure(): Structure
    {
        Warehouse::create(['warehouse' => 'Y', 'description' => 'Second warehouse',
            'multiple' => true, 'enabled' => true]);
        $structure = Structure::create(['id' => 'second-address', 'warehouse' => 'Y',
            'hall' => 56, 'position' => 78, 'level' => 2, 'enabled' => true]);
        $this->bodies[1]->update(['structure_id' => $structure->id]);

        return $structure;
    }

    public function test_all_zero_finalization_recalculates_stale_filled_to_false(): void
    {
        foreach ($this->bodies as $body) {
            $body->update(['quantity' => '0.0000']);
        }
        $this->structure->update(['filled' => true]);
        $this->finalize();
        $this->assertFalse($this->structure->fresh()->filled);
        $this->assertDatabaseCount('stock', 0);
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_zero_finalization_preserves_stock_occupancy(): void
    {
        foreach ($this->bodies as $body) {
            $body->update(['quantity' => '0.0000']);
        }
        $other = ReceptionBody::create(['header' => $this->header->id, 'product_id' => $this->bodies[0]->product_id]);
        $this->existingStock($other);
        $other->update(['header' => $this->otherHeader()->id]);
        $this->finalize();
        $this->assertTrue($this->structure->fresh()->filled);
        $this->assertDatabaseCount('stock', 1);
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_zero_finalization_preserves_positive_pending_occupancy(): void
    {
        foreach ($this->bodies as $body) {
            $body->update(['quantity' => '0.0000']);
        }
        ReceptionBody::create(['header' => $this->otherHeader()->id,
            'product_id' => $this->bodies[0]->product_id, 'structure_id' => $this->structure->id,
            'received' => true, 'quantity' => '0.0001']);
        $this->finalize();
        $this->assertTrue($this->structure->fresh()->filled);
        $this->assertDatabaseCount('stock', 0);
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_occupancy_excludes_one_pending_body_and_ignores_finalized_bodies(): void
    {
        $occupancy = app(\App\Services\StructureOccupancy::class);
        $this->bodies[1]->update(['quantity' => '0.0000']);
        $this->assertTrue($occupancy->isOccupied($this->structure));
        $this->assertFalse($occupancy->isOccupied($this->structure, $this->bodies[0]->row));
        $stock = $this->existingStock($this->bodies[0]);
        $this->bodies[0]->update(['stock_id' => $stock->id]);
        $destination = $this->secondStructure();
        $stock->update(['warehouse' => $destination->warehouse, 'hall' => $destination->hall,
            'position' => $destination->position, 'level' => $destination->level]);
        $this->assertFalse($occupancy->isOccupied($this->structure));
        $this->assertTrue($occupancy->isOccupied($destination));
        $this->assertFalse($occupancy->recalculate($this->structure));
        $this->assertFalse($this->structure->fresh()->filled);
    }

    public function test_failure_rolls_back_zero_structure_recalculation(): void
    {
        $zeroStructure = $this->secondStructure();
        $this->bodies[1]->update(['quantity' => '0.0000']);
        $zeroStructure->update(['filled' => true]);
        $events = Journal::getEventDispatcher();
        Journal::setEventDispatcher(clone $events);
        ReceptionBody::create(['header' => $this->header->id, 'product_id' => $this->bodies[0]->product_id,
            'quantity' => '1.0000', 'received' => true, 'user_id' => $this->bodies[0]->user_id,
            'structure_id' => $this->structure->id]);
        $this->header->update(['rows' => 3]);
        app(\App\Services\StructureOccupancy::class)->recalculate($this->structure);
        $count = 0;
        Journal::creating(function () use (&$count, $zeroStructure) {
            if (++$count === 2) {
                $this->assertFalse($zeroStructure->fresh()->filled);
                throw new RuntimeException('Forced occupancy rollback');
            }
        });
        try {
            try {
                $this->finalize();
                $this->fail('Expected failure');
            } catch (RuntimeException $exception) {
                $this->assertSame('Forced occupancy rollback', $exception->getMessage());
            }
        } finally {
            Journal::setEventDispatcher($events);
        }
        $this->assertTrue($zeroStructure->fresh()->filled);
        $this->assertTrue($this->structure->fresh()->filled);
        $this->assertDatabaseCount('stock', 0);
        $this->assertDatabaseCount('journals', 0);
        $this->assertSame(0, ReceptionBody::where('header', $this->header->id)->whereNotNull('stock_id')->count());
        $this->assertSame('reception in progress', $this->header->fresh()->status);
    }

    private function otherHeader(): ReceptionHeader
    {
        return ReceptionHeader::create(['supplier_id' => $this->header->supplier_id,
            'xml_nf_header_id' => $this->header->xml_nf_header_id, 'rows' => 1, 'status' => 'reception in progress']);
    }

    public static function incompleteFields(): array
    {
        return [['received', false], ['quantity', null], ['user_id', null], ['structure_id', null], ['quantity', '-0.0001']];
    }

    #[DataProvider('incompleteFields')]
    public function test_incomplete_or_negative_conference_is_rejected(string $field, mixed $value): void
    {
        $this->bodies[1]->update([$field => $value]);
        $this->assertRejected();
    }

    public function test_missing_body_is_rejected(): void
    {
        $this->bodies[1]->delete();
        $this->assertRejected();
    }

    public function test_extra_body_count_is_rejected(): void
    {
        $this->header->update(['rows' => 1]);
        $this->assertRejected();
    }

    public function test_disabled_reception_is_rejected(): void
    {
        $this->header->update(['enabled' => false]);
        $this->assertRejected();
    }

    public static function invalidStatuses(): array
    {
        return [['to receive'], ['on hold'], ['canceled'], ['received']];
    }

    #[DataProvider('invalidStatuses')]
    public function test_invalid_source_status_is_rejected(string $status): void
    {
        $this->header->update(['status' => $status]);
        $this->assertRejected();
    }

    public function test_retry_after_success_does_not_duplicate_movements(): void
    {
        $this->finalize();
        try {
            $this->finalize();
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('header', $exception->errors());
        }
        $this->assertDatabaseCount('stock', 2);
        $this->assertDatabaseCount('journals', 2);
    }

    public function test_preexisting_stock_rejects_finalization_without_duplicate_movement(): void
    {
        $stock = $this->existingStock($this->bodies[1]);
        try {
            $this->finalize();
            $this->fail('Expected rejection');
        } catch (ValidationException|QueryException $exception) {
        }
        $this->assertDatabaseCount('stock', 1);
        $this->assertDatabaseCount('journals', 0);
        $this->assertDatabaseHas('stock', ['id' => $stock->id]);
        $this->assertUnchanged();
    }

    public function test_database_enforces_unique_reception_id(): void
    {
        $this->existingStock($this->bodies[0]);
        $this->expectException(QueryException::class);
        $this->existingStock($this->bodies[0]);
    }

    public function test_failure_on_second_journal_rolls_back_every_write(): void
    {
        app(\App\Services\StructureOccupancy::class)->recalculate($this->structure);
        $events = Journal::getEventDispatcher();
        Journal::setEventDispatcher(clone $events);
        $count = 0;
        Journal::creating(function () use (&$count) {
            if (++$count === 2) {
                $this->assertDatabaseCount('stock', 2);
                $this->assertDatabaseCount('journals', 1);
                $this->assertNotNull($this->bodies[0]->fresh()->stock_id);
                $this->assertSame('reception in progress', $this->header->fresh()->status);
                throw new RuntimeException('Forced journal failure');
            }
        });
        try {
            try {
                $this->finalize();
                $this->fail('Expected failure');
            } catch (RuntimeException $exception) {
                $this->assertSame('Forced journal failure', $exception->getMessage());
            }
        } finally {
            Journal::setEventDispatcher($events);
        }
        $this->assertDatabaseCount('stock', 0);
        $this->assertDatabaseCount('journals', 0);
        $this->assertUnchanged();
        $this->assertTrue($this->structure->fresh()->filled);
        $this->assertTrue(app(\App\Services\StructureOccupancy::class)->isOccupied($this->structure));
    }

    public function test_manager_delegates_finalization_and_rejects_incomplete_conference(): void
    {
        $this->bodies[1]->update(['received' => false]);
        Livewire::test(ReceptionHeaderManager::class)->call('edit', $this->header->id)
            ->set('status', 'received')->call('update')->assertHasErrors('header');
        $this->assertUnchanged();
        $this->bodies[1]->update(['received' => true]);
        Livewire::test(ReceptionHeaderManager::class)->call('edit', $this->header->id)
            ->set('status', 'received')->call('update')->assertHasNoErrors();
        $this->assertSame('received', $this->header->fresh()->status);
        $this->assertDatabaseCount('stock', 2);
    }

    private function finalize(): void
    {
        app(ReceptionFinalizer::class)->finalize($this->header->id);
    }

    private function assertRejected(): void
    {
        $status = $this->header->fresh()->status;
        try {
            $this->finalize();
            $this->fail('Expected rejection');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('header', $exception->errors());
        }
        $this->assertSame($status, $this->header->fresh()->status);
        $this->assertDatabaseCount('stock', 0);
        $this->assertDatabaseCount('journals', 0);
        foreach ($this->bodies as $body) {
            if ($body->fresh()) {
                $this->assertNull($body->fresh()->stock_id);
            }
        }
    }

    private function assertUnchanged(): void
    {
        $this->assertSame('reception in progress', $this->header->fresh()->status);
        foreach ($this->bodies as $body) {
            $this->assertNull($body->fresh()->stock_id);
        }
    }

    private function existingStock(ReceptionBody $body): Stock
    {
        return Stock::create(['reception_id' => $body->row, 'product_id' => $body->product_id,
            'supplier_id' => $this->header->supplier_id, 'quantity' => '1.0000', 'status' => 'stored',
            'immobilized' => false, 'warehouse' => $this->structure->warehouse,
            'hall' => 12, 'position' => 34, 'level' => 5]);
    }
}
