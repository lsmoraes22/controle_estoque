<?php

namespace Tests\Feature;

use App\Livewire\ReceptionBodyManager;
use App\Models\ReceptionBody;
use App\Models\ReceptionHeader;
use App\Models\Stock;
use App\Models\Structure;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\XmlNfBody;
use App\Models\XmlNfHeader;
use App\Models\Journal;
use App\Models\User;
use App\Services\Nfe\NfeInboundImporter;
use App\Services\Nfe\NfeInboundParser;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

class ReceptionPhysicalCheckTest extends DatabaseTestCase
{
    private User $operator;
    private ReceptionHeader $reception;
    private ReceptionBody $firstItem;
    private ReceptionBody $secondItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operator = User::factory()->create();
        $this->actingAs($this->operator);

        $supplier = Supplier::query()->create([
            'supplier' => 'Ficticio',
            'address' => 'Endereco ficticio',
            'phone1' => '00000000000',
            'email' => 'physical-check@example.test',
            'cnpj' => '00000000000000',
        ]);
        $xml = file_get_contents(base_path('tests/Fixtures/nfe/inbound-ficticia.xml'));
        $result = (new NfeInboundImporter())->import((new NfeInboundParser())->parse($xml));
        $this->reception = $result->receptionHeader;
        $this->firstItem = ReceptionBody::query()->where('header', $this->reception->id)->where('xml_nf_body_id', '<>', null)->orderBy('row')->firstOrFail();
        $this->secondItem = ReceptionBody::query()->where('header', $this->reception->id)->where('row', '>', $this->firstItem->row)->firstOrFail();
    }

    public function test_imported_item_starts_unchecked_with_no_physical_quantity_or_address(): void
    {
        $this->assertFalse($this->firstItem->received);
        $this->assertNull($this->firstItem->quantity);
        $this->assertNull($this->firstItem->structure_id);
        $this->assertNull($this->firstItem->user_id);
    }

    public function test_operator_can_confirm_equal_fractional_or_different_quantities(): void
    {
        $structure = $this->structure();
        $this->confirm($this->firstItem, $structure, '2.5000');
        $this->assertSame('2.5000', $this->firstItem->fresh()->quantity);

        $otherStructure = $this->structure('B');
        $this->confirm($this->secondItem, $otherStructure, '1.2500');
        $this->assertSame('1.2500', $this->secondItem->fresh()->quantity);
        $this->assertNotSame($this->secondItem->theoretical, $this->secondItem->fresh()->quantity);
    }

    public function test_zero_quantity_is_valid_and_negative_quantity_is_rejected(): void
    {
        $structure = $this->structure();
        $this->confirm($this->firstItem, $structure, '0.0000');
        $this->assertSame('0.0000', $this->firstItem->fresh()->quantity);

        $this->assertValidationFailure($this->secondItem, $structure, '-0.0001', 'quantity');
        $this->assertFalse($this->secondItem->fresh()->received);
        $this->assertNull($this->secondItem->fresh()->quantity);
    }

    public function test_quantity_rejects_more_than_four_decimal_places_or_overflow(): void
    {
        $structure = $this->structure();
        $this->assertValidationFailure($this->firstItem, $structure, '1.00001', 'quantity');
        $this->assertValidationFailure($this->firstItem, $structure, '12345678901234567', 'quantity');
    }

    public function test_confirmation_saves_selected_structure_authenticated_user_and_header_progress(): void
    {
        $structure = $this->structure();
        $this->confirm($this->firstItem, $structure, '2.5000');

        $this->assertSame($structure->id, $this->firstItem->fresh()->structure_id);
        $this->assertSame($this->operator->id, $this->firstItem->fresh()->user_id);
        $this->assertTrue($this->firstItem->fresh()->received);
        $this->assertSame('reception in progress', $this->reception->fresh()->status);
        $this->assertTrue($structure->fresh()->filled);
    }

    public function test_batch_and_dates_may_be_null_or_valid_dates_are_saved(): void
    {
        $structure = $this->structure();
        $this->confirm($this->firstItem, $structure, '2.5000', null, null, null);
        $item = $this->firstItem->fresh();
        $this->assertNull($item->batch);
        $this->assertNull($item->fabrication);
        $this->assertNull($item->validity);

        $secondStructure = $this->structure('B');
        $this->confirm($this->secondItem, $secondStructure, '3.0000', 'BATCH-02', '2026-09-01', '2027-09-01');
        $this->assertSame('BATCH-02', $this->secondItem->fresh()->batch);
        $this->assertSame('2026-09-01', $this->secondItem->fresh()->fabrication);
        $this->assertSame('2027-09-01', $this->secondItem->fresh()->validity);
    }

    public function test_invalid_dates_are_rejected(): void
    {
        $structure = $this->structure();
        $component = $this->componentFor($this->firstItem, $structure, '1.0000')
            ->set('fabrication', '2026-02-30')
            ->call('update');

        $component->assertHasErrors(['fabrication' => 'date_format']);
        $this->assertFalse($this->firstItem->fresh()->received);
    }

    public function test_nonexistent_and_disabled_structures_are_rejected(): void
    {
        $this->componentFor($this->firstItem, null, '1.0000')
            ->set('structure_id', 'missing-address')
            ->call('update')
            ->assertHasErrors('structure_id');

        $disabled = $this->structure('B', false);
        $component = $this->componentFor($this->firstItem, $disabled, '1.0000')->call('update');
        $component->assertHasErrors('structure_id');
        $this->assertFalse($this->firstItem->fresh()->received);
    }

    public function test_disabled_warehouse_is_rejected(): void
    {
        $structure = $this->structure('B', true, false);
        Livewire::test(ReceptionBodyManager::class, ['header' => $this->reception->id])
            ->call('edit', $this->firstItem->row)
            ->set('quantity', '1.0000')
            ->set('structure_id', $structure->id)
            ->call('update')
            ->assertHasErrors('structure_id');

        $this->assertFalse($this->firstItem->fresh()->received);
    }

    public function test_occupied_position_is_rejected_when_warehouse_disallows_multiple(): void
    {
        $structure = $this->structure();
        $structure->update(['filled' => true]);

        $this->componentFor($this->firstItem, $structure, '1.0000')
            ->call('update')
            ->assertHasErrors('structure_id');
        $this->assertFalse($this->firstItem->fresh()->received);
    }

    public function test_selected_new_structure_is_checked_instead_of_existing_structure(): void
    {
        $occupied = $this->structure();
        $occupied->update(['filled' => true]);
        $this->firstItem->update(['structure_id' => $occupied->id]);
        $available = $this->structure('B');

        $this->confirm($this->firstItem, $available, '2.5000');

        $this->assertSame($available->id, $this->firstItem->fresh()->structure_id);
        $this->assertTrue($available->fresh()->filled);
        $this->assertFalse($occupied->fresh()->filled);
    }

    public function test_unconfirm_preserves_item_and_xml_traceability_without_stock_or_journal(): void
    {
        $structure = $this->structure();
        $this->confirm($this->firstItem, $structure, '2.5000');
        $xmlBodyId = $this->firstItem->fresh()->xml_nf_body_id;

        Livewire::test(ReceptionBodyManager::class, ['header' => $this->reception->id])
            ->call('edit', $this->firstItem->row)
            ->set('received', false)
            ->call('update');

        $item = $this->firstItem->fresh();
        $this->assertFalse($item->received);
        $this->assertSame('2.5000', $item->quantity);
        $this->assertSame($xmlBodyId, $item->xml_nf_body_id);
        $this->assertDatabaseHas('xml_nf_body', ['id' => $xmlBodyId]);
        $this->assertDatabaseCount('stock', 0);
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_unconfirm_keeps_structure_filled_when_another_item_still_occupies_it(): void
    {
        $structure = $this->structure();
        $structure->warehouseModel()->update(['multiple' => true]);
        $this->confirm($this->firstItem, $structure, '2.5000');
        $this->confirm($this->secondItem, $structure, '3.0000');

        Livewire::test(ReceptionBodyManager::class, ['header' => $this->reception->id])
            ->call('edit', $this->firstItem->row)
            ->set('received', false)
            ->call('update');

        $this->assertTrue($structure->fresh()->filled);
        $this->assertTrue($this->secondItem->fresh()->received);
    }

    public function test_unconfirm_keeps_structure_filled_when_stock_still_occupies_it(): void
    {
        $structure = $this->structure();
        $this->confirm($this->firstItem, $structure, '2.5000');
        $this->insertStockAt($structure, $this->firstItem);

        Livewire::test(ReceptionBodyManager::class, ['header' => $this->reception->id])
            ->call('edit', $this->firstItem->row)
            ->set('received', false)
            ->call('update');

        $this->assertTrue($structure->fresh()->filled);
        $this->assertDatabaseCount('stock', 1);
    }

    public function test_received_canceled_disabled_and_on_hold_headers_block_conference_changes(): void
    {
        foreach (['received', 'canceled', 'on hold'] as $status) {
            $this->reception->update(['status' => $status, 'enabled' => true]);
            Livewire::test(ReceptionBodyManager::class, ['header' => $this->reception->id])
                ->call('edit', $this->firstItem->row)
                ->assertHasErrors('header');
        }

        $this->reception->update(['status' => 'reception in progress', 'enabled' => false]);
        Livewire::test(ReceptionBodyManager::class, ['header' => $this->reception->id])
            ->call('edit', $this->firstItem->row)
            ->assertHasErrors('header');
        $this->assertFalse($this->firstItem->fresh()->received);
    }

    public function test_received_header_cannot_be_unconfirmed(): void
    {
        $structure = $this->structure();
        $this->confirm($this->firstItem, $structure, '2.5000');
        $this->reception->update(['status' => 'received']);

        Livewire::test(ReceptionBodyManager::class, ['header' => $this->reception->id])
            ->call('edit', $this->firstItem->row)
            ->assertHasErrors('header');

        $this->assertTrue($this->firstItem->fresh()->received);
    }

    public function test_every_conference_operation_does_not_create_stock_or_journal(): void
    {
        $this->confirm($this->firstItem, $this->structure(), '2.5000');
        $this->assertDatabaseCount('stock', 0);
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_conference_requires_an_authenticated_user(): void
    {
        auth()->logout();
        $structure = $this->structure();

        Livewire::test(ReceptionBodyManager::class, ['header' => $this->reception->id])
            ->call('edit', $this->firstItem->row)
            ->set('quantity', '1.0000')
            ->set('structure_id', $structure->id)
            ->call('update')
            ->assertHasErrors('user_id');

        $this->assertFalse($this->firstItem->fresh()->received);
        $this->assertNull($this->firstItem->fresh()->user_id);
    }

    private function confirm(
        ReceptionBody $item,
        Structure $structure,
        string $quantity,
        ?string $batch = 'BATCH-01',
        ?string $fabrication = '2026-09-01',
        ?string $validity = '2027-09-01',
    ): void {
        $this->componentFor($item, $structure, $quantity)
            ->set('batch', $batch)
            ->set('fabrication', $fabrication)
            ->set('validity', $validity)
            ->call('update')
            ->assertHasNoErrors();
    }

    private function assertValidationFailure(ReceptionBody $item, Structure $structure, string $quantity, string $field): void
    {
        $this->componentFor($item, $structure, $quantity)
            ->call('update')
            ->assertHasErrors($field);
    }

    private function componentFor(ReceptionBody $item, ?Structure $structure, string $quantity)
    {
        $component = Livewire::test(ReceptionBodyManager::class, ['header' => $this->reception->id])
            ->call('edit', $item->row)
            ->set('quantity', $quantity);
        if ($structure) {
            $component->set('structure_id', $structure->id);
        }

        return $component;
    }

    private function structure(string $warehouseCode = 'A', bool $enabled = true, bool $warehouseEnabled = true): Structure
    {
        Warehouse::query()->firstOrCreate(
            ['warehouse' => $warehouseCode],
            ['description' => 'Warehouse '.$warehouseCode, 'multiple' => false, 'enabled' => $warehouseEnabled],
        )->update(['enabled' => $warehouseEnabled]);

        static $counter = 0;
        $counter++;
        $hall = (int) ceil($counter / 100);
        $position = $counter;
        $id = $warehouseCode.str_pad((string) $hall, 6, '0', STR_PAD_LEFT)
            .str_pad((string) $position, 6, '0', STR_PAD_LEFT).'0001';

        return Structure::query()->create([
            'id' => $id,
            'warehouse' => $warehouseCode,
            'hall' => $hall,
            'position' => $position,
            'level' => 1,
            'enabled' => $enabled,
        ]);
    }

    private function insertStockAt(Structure $structure, ReceptionBody $item): void
    {
        DB::table('stock')->insert([
            'reception_id' => $item->row,
            'product_id' => $item->product_id,
            'supplier_id' => $this->reception->supplier_id,
            'fabrication' => null,
            'validity' => null,
            'batch' => null,
            'immobilized' => false,
            'status' => 'stored',
            'quantity' => 1,
            'warehouse' => $structure->warehouse,
            'hall' => $structure->hall,
            'position' => $structure->position,
            'level' => $structure->level,
        ]);
    }
}