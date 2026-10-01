<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSectorPermission;
use App\Models\Product;
use App\Models\Sector;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\XmlNfBody;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\DatabaseTestCase;

class StructuralSmokeTest extends DatabaseTestCase
{
    public function test_application_boots_and_guests_reach_login(): void
    {
        $this->get('/up')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/')->assertRedirect('/login');
    }

    public function test_migrations_create_the_expected_schema_and_relationships(): void
    {
        foreach (['sectors', 'users', 'password_reset_tokens', 'sessions', 'cache',
            'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'suppliers',
            'immobilizations', 'categories', 'products', 'warehouses', 'structures',
            'request_headers', 'requests', 'permissions', 'sector_permissions',
            'xml_nf_header', 'xml_nf_body', 'reception_headers', 'reception_body',
            'stock', 'personal_access_tokens', 'journals'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }

        $this->assertFalse(Schema::hasTable('stocks'));
        $this->assertFalse(Schema::hasColumn('stock', 'theoretical'));
        $this->assertTrue(Schema::hasColumn('reception_body', 'theoretical'));
        $this->assertSame('header', (new XmlNfBody)->XmlNfHeader()->getForeignKeyName());

        $keys = collect(Schema::getForeignKeys('stock'));
        $location = $keys->firstWhere('foreign_table', 'structures');
        $this->assertNotNull($location);
        $this->assertSame(['warehouse', 'hall', 'position', 'level'], $location['columns']);
        $this->assertSame($location['columns'], $location['foreign_columns']);
        $this->assertSame('reception_body', $keys->firstWhere('columns', ['reception_id'])['foreign_table']);
        $this->assertSame('stock', collect(Schema::getForeignKeys('reception_body'))
            ->firstWhere('columns', ['stock_id'])['foreign_table']);
    }

    public function test_deleting_stock_preserves_reception_body_and_clears_stock_id(): void
    {
        Warehouse::create(['warehouse' => 'A', 'enabled' => true]);
        DB::table('structures')->insert([
            'id' => 'A-1-1-1', 'warehouse' => 'A', 'hall' => 1, 'position' => 1, 'level' => 1,
        ]);
        Product::create([
            'id' => 'FK-001', 'description' => 'Stock deletion fixture', 'category_id' => 1,
        ]);
        $supplierId = DB::table('suppliers')->insertGetId([
            'supplier' => 'FK fixture', 'address' => 'Test address', 'phone1' => '11999999999',
            'email' => 'fk@example.com', 'cnpj' => '00000000000000',
        ]);
        $xmlId = DB::table('xml_nf_header')->insertGetId(['tipoNF' => 'compra', 'idnf' => 'FK-001']);
        $headerId = DB::table('reception_headers')->insertGetId([
            'supplier_id' => $supplierId, 'xml_nf_header_id' => $xmlId,
            'rows' => 1, 'status' => 'received',
        ]);
        $rowId = DB::table('reception_body')->insertGetId([
            'header' => $headerId, 'product_id' => 'FK-001', 'quantity' => 5, 'received' => true,
        ], 'row');
        $stockId = DB::table('stock')->insertGetId([
            'reception_id' => $rowId, 'product_id' => 'FK-001', 'quantity' => 5,
            'immobilized' => false, 'status' => 'stored',
            'warehouse' => 'A', 'hall' => 1, 'position' => 1, 'level' => 1,
        ]);
        DB::table('reception_body')->where('row', $rowId)->update(['stock_id' => $stockId]);
        $this->assertDatabaseHas('reception_body', ['row' => $rowId, 'stock_id' => $stockId]);

        DB::table('stock')->where('id', $stockId)->delete();

        $this->assertDatabaseMissing('stock', ['id' => $stockId]);
        $this->assertDatabaseHas('reception_body', [
            'row' => $rowId, 'header' => $headerId, 'product_id' => 'FK-001',
            'quantity' => 5, 'received' => true, 'stock_id' => null,
        ]);
    }

    public function test_seed_is_repeatable_and_supports_minimal_navigation(): void
    {
        $this->seed();
        $this->seed();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('sectors', 1);
        $this->assertDatabaseCount('permissions', 1);
        $this->assertDatabaseCount('sector_permissions', 1);
        $this->assertDatabaseCount('stock', 0);
        $this->assertDatabaseCount('xml_nf_header', 0);

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('Demo', $user->sector->sector);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/home');
        $this->get('/home')->assertOk();
        $this->get('/users')->assertOk();
    }

    public function test_user_factory_creates_its_required_sector(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(Sector::class, $user->sector);
        $this->assertDatabaseHas('sectors', ['id' => $user->sector_id]);
    }

    public function test_essential_pages_resolve_components_and_views(): void
    {
        $this->seed();
        // This tests rendering only; authorization behavior is a later round.
        $this->withoutMiddleware(CheckSectorPermission::class);
        $this->actingAs(User::firstOrFail())->withSession(['permissions' => []]);
        foreach (['/', '/home', '/users', '/sectors', '/permissions', '/suppliers',
            '/products', '/warehouses', '/structures', '/receptions'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_textual_keys_survive_persistence(): void
    {
        $warehouse = Warehouse::create(['warehouse' => 'A', 'enabled' => true]);
        $this->assertSame('A', $warehouse->fresh()->getKey());
        $product = Product::create([
            'id' => 'DEMO-001', 'description' => 'Synthetic structural fixture',
            'category_id' => 1,
        ]);
        $this->assertSame('DEMO-001', $product->fresh()->getKey());
    }
}
