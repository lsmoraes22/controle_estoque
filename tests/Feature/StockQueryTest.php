<?php

namespace Tests\Feature;

use App\Livewire\StockManager;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ReceptionBody;
use App\Models\ReceptionHeader;
use App\Models\SectorPermission;
use App\Models\Stock;
use App\Models\Structure;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\XmlNfHeader;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;

class StockQueryTest extends DatabaseTestCase
{
    private User $user;

    private ReceptionHeader $header;

    private Supplier $supplier;

    private Stock $first;

    private Stock $second;

    private Stock $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['level' => 2]);
        $permission = Permission::forceCreate(['route' => 'stocks', 'menu' => 'inventory', 'menu_label' => 'Inventory', 'route_label' => 'Stock', 'icon_menu' => 'bi bi-box', 'icon_route' => 'bi bi-box']);
        SectorPermission::create(['sector_id' => $this->user->sector_id, 'permission_id' => $permission->id,
            'read_write' => 'R', 'level' => 1, 'enabled' => true]);
        $this->supplier = Supplier::create(['supplier' => 'Trace supplier', 'address' => 'Test',
            'phone1' => '00000000000', 'email' => 'stock@example.test', 'cnpj' => '00000000000000']);
        $xml = XmlNfHeader::create(['tipoNF' => 'compra', 'idnf' => 'stock-query']);
        $this->header = ReceptionHeader::create(['supplier_id' => $this->supplier->id,
            'xml_nf_header_id' => $xml->id, 'rows' => 3, 'status' => 'received']);
        foreach (['A', 'B'] as $code) {
            Warehouse::create(['warehouse' => $code, 'enabled' => true]);
            Structure::create(['id' => $code.'-query', 'warehouse' => $code, 'hall' => 0, 'position' => 1, 'level' => 0]);
        }
        Product::create(['id' => 'ITEM-A', 'description' => 'Copper widget', 'unit' => 'KG', 'category_id' => 1]);
        Product::create(['id' => 'ITEM-B', 'description' => 'Steel widget', 'unit' => 'UN', 'category_id' => 1]);
        $this->first = $this->stock();
        $this->second = $this->stock();
        $this->other = $this->stock('ITEM-B', 'B', 'LOT-Y', 'to store');
    }

    private function stock(string $product = 'ITEM-A', string $warehouse = 'A', ?string $batch = 'LOT-X', string $status = 'stored'): Stock
    {
        $body = ReceptionBody::create(['header' => $this->header->id, 'product_id' => $product]);

        return Stock::create(['reception_id' => $body->row, 'product_id' => $product,
            'supplier_id' => $this->supplier->id, 'quantity' => '10.0000', 'immobilized' => false,
            'warehouse' => $warehouse, 'hall' => 0, 'position' => 1, 'level' => 0,
            'batch' => $batch, 'status' => $status]);
    }

    public function test_guests_redirect_to_login(): void
    {
        $this->get('/stocks')->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_render_page_with_read_permission(): void
    {
        $this->actingAs($this->user)->withSession(['permissions' => []])->get(route('stocks.index'))
            ->assertOk()->assertSeeLivewire(StockManager::class)->assertSee('Copper widget');
    }

    public function test_user_without_stock_permission_is_redirected(): void
    {
        $this->actingAs(User::factory()->create(['level' => 2]))->get('/stocks')->assertRedirect('/home');
    }

    public function test_identical_stock_rows_remain_separate_and_related_data_is_displayed(): void
    {
        $this->actingAs($this->user);
        $this->assertTrue($this->first->receptionBody->is(ReceptionBody::findOrFail($this->first->reception_id)));
        Livewire::test(StockManager::class)->assertSee('Copper widget')->assertSee('Trace supplier')->assertSee('KG')
            ->assertSeeHtml('wire:key="stock-'.$this->first->id.'"')
            ->assertSeeHtml('wire:key="stock-'.$this->second->id.'"')
            ->assertViewHas('stocks', fn ($stocks) => $stocks->total() === 3 && $stocks->pluck('id')->all() === [$this->first->id, $this->second->id, $this->other->id]);
    }

    public function test_exact_decimal_and_zero_quantities_and_optional_nulls_are_displayed(): void
    {
        $this->first->update(['quantity' => '1234567890123456.1234']);
        $this->second->update(['quantity' => '0.0000', 'batch' => null, 'supplier_id' => null]);
        $this->actingAs($this->user);
        Livewire::test(StockManager::class)->assertSee('1234567890123456.1234')->assertSee('0.0000')->assertSee('—')
            ->assertViewHas('stocks', fn ($stocks) => $stocks[0]->hall === 0 && $stocks[0]->level === 0);
    }

    public static function filters(): array
    {
        return [['search', 'ITEM-A'], ['search', 'Copper'], ['warehouse', 'A'], ['batch', 'OT-X'], ['status', 'stored']];
    }

    #[DataProvider('filters')]
    public function test_independent_filters(string $field, string $value): void
    {
        $this->actingAs($this->user);
        Livewire::test(StockManager::class)->set($field, $value)
            ->assertViewHas('stocks', fn ($stocks) => $stocks->pluck('id')->all() === [$this->first->id, $this->second->id])
            ->set($field, '')->assertViewHas('stocks', fn ($stocks) => $stocks->total() === 3);
    }

    #[DataProvider('filters')]
    public function test_filter_whitespace_is_ignored_without_mutating_public_values(string $field, string $value): void
    {
        $this->actingAs($this->user);
        $padded = " \t".$value." \n";
        Livewire::test(StockManager::class)->set($field, $padded)
            ->assertSet($field, $padded)
            ->assertViewHas('stocks', fn ($stocks) => $stocks->pluck('id')->all() === [$this->first->id, $this->second->id]);
    }

    #[DataProvider('filters')]
    public function test_whitespace_only_filter_imposes_no_restriction(string $field, string $value): void
    {
        $this->actingAs($this->user);
        $whitespace = " \t\n ";
        Livewire::test(StockManager::class)->set($field, $value)
            ->set($field, $whitespace)->assertSet($field, $whitespace)
            ->assertViewHas('stocks', fn ($stocks) => $stocks->pluck('id')->all() === [$this->first->id, $this->second->id, $this->other->id]);
    }

    public function test_filters_combine_with_and_including_product_or_description(): void
    {
        $this->second->update(['batch' => 'LOT-Z']);
        $this->actingAs($this->user);
        Livewire::test(StockManager::class)->set('search', 'ITEM-A')->set('warehouse', 'B')
            ->assertViewHas('stocks', fn ($stocks) => $stocks->total() === 0)
            ->set('search', 'Copper')->set('warehouse', 'A')->set('batch', 'LOT-X')->set('status', 'stored')
            ->assertViewHas('stocks', fn ($stocks) => $stocks->pluck('id')->all() === [$this->first->id]);
    }

    #[DataProvider('filters')]
    public function test_each_filter_resets_pagination(string $field, string $value): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->stock();
        }
        $this->actingAs($this->user);
        Livewire::test(StockManager::class)->assertViewHas('stocks', fn ($stocks) => $stocks->count() === 10)
            ->call('setPage', 2)->assertSet('paginators.page', 2)
            ->set($field, $value)->assertSet('paginators.page', 1);
    }

    public function test_read_query_eager_loads_relations_and_does_not_mutate_stock_or_journal(): void
    {
        $this->actingAs($this->user);
        $before = DB::table('stock')->orderBy('id')->get()->toJson();
        $bodiesBefore = DB::table('reception_body')->orderBy('row')->get()->toJson();
        $journalsBefore = DB::table('journals')->get()->toJson();
        DB::enableQueryLog();
        Livewire::test(StockManager::class)->assertViewHas('stocks', function ($stocks) {
            return $stocks->every(fn ($stock) => $stock->relationLoaded('product') && $stock->relationLoaded('supplier'));
        })->set('search', 'Copper')->set('batch', 'LOT-X')->set('warehouse', 'A')->set('status', 'stored');
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();
        $this->assertFalse($queries->contains(fn ($sql) => preg_match('/^\s*(insert|update|delete|replace|alter|drop)\b/i', $sql) === 1));
        $this->assertSame($before, DB::table('stock')->orderBy('id')->get()->toJson());
        $this->assertSame($bodiesBefore, DB::table('reception_body')->orderBy('row')->get()->toJson());
        $this->assertSame($journalsBefore, DB::table('journals')->get()->toJson());
        $this->assertDatabaseCount('journals', 0);
    }
}
