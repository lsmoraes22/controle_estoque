<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ReceptionBody;
use App\Models\ReceptionHeader;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\XmlNfBody;
use App\Models\XmlNfHeader;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SimpleXMLElement;
use Tests\DatabaseTestCase;

class NfeDomainSchemaTest extends DatabaseTestCase
{
    public function test_supplier_products_schema_exists(): void
    {
        $this->assertTrue(Schema::hasColumns('supplier_products', [
            'id', 'supplier_id', 'supplier_product_code', 'product_id', 'created_at', 'updated_at',
        ]));
    }

    public function test_supplier_code_is_unique_within_one_supplier(): void
    {
        $supplier = $this->supplier('1');
        $first = $this->product('INTERNAL-001');
        $second = $this->product('INTERNAL-002');
        $this->mapping($supplier, $first, '000123');

        $this->assertDatabaseError(1062, fn () => $this->mapping($supplier, $second, '000123'));
        $this->assertDatabaseCount('supplier_products', 1);
        $this->assertDatabaseHas('supplier_products', ['product_id' => $first->id]);
    }

    public function test_different_suppliers_can_map_the_same_code_to_different_products(): void
    {
        $supplierA = $this->supplier('1');
        $supplierB = $this->supplier('2');
        $productA = $this->product('INTERNAL-001');
        $productB = $this->product('INTERNAL-002');
        $code = '000'.str_repeat('X', 57);
        $mappingA = $this->mapping($supplierA, $productA, $code)->fresh();
        $mappingB = $this->mapping($supplierB, $productB, $code)->fresh();

        $this->assertDatabaseCount('supplier_products', 2);
        $this->assertSame($code, $mappingA->supplier_product_code);
        $this->assertTrue($mappingA->supplier->is($supplierA));
        $this->assertTrue($mappingB->supplier->is($supplierB));
        $this->assertTrue($mappingA->product->is($productA));
        $this->assertTrue($mappingB->product->is($productB));
        $this->assertTrue($supplierA->supplierProducts->sole()->is($mappingA));
        $this->assertTrue($supplierB->supplierProducts->sole()->is($mappingB));
        $this->assertTrue($productA->supplierProducts->sole()->is($mappingA));
        $this->assertTrue($productB->supplierProducts->sole()->is($mappingB));
    }

    public function test_supplier_product_foreign_keys_reject_missing_parents(): void
    {
        $supplier = $this->supplier('1');
        $product = $this->product('INTERNAL-001');

        $this->assertDatabaseError(1452, fn () => SupplierProduct::create([
            'supplier_id' => $supplier->id + 1000,
            'supplier_product_code' => 'CODE', 'product_id' => $product->id,
        ]));
        $this->assertDatabaseError(1452, fn () => SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'supplier_product_code' => 'CODE', 'product_id' => 'MISSING',
        ]));
        $this->assertDatabaseCount('supplier_products', 0);
    }

    public function test_supplier_product_mapping_restricts_parent_deletion(): void
    {
        $supplier = $this->supplier('1');
        $product = $this->product('INTERNAL-001');
        $mapping = $this->mapping($supplier, $product, 'CODE');

        $this->assertDatabaseError(1451, fn () => $supplier->delete());
        $this->assertDatabaseError(1451, fn () => $product->delete());
        $this->assertDatabaseHas('supplier_products', ['id' => $mapping->id]);
    }

    public function test_xml_item_number_can_repeat_in_different_documents(): void
    {
        $first = $this->xmlItem($this->xmlHeader('1'), '1');
        $second = $this->xmlItem($this->xmlHeader('2'), '1');

        $this->assertNotSame($first->header, $second->header);
        $this->assertSame('1', $first->fresh()->nItem);
        $this->assertSame('1', $second->fresh()->nItem);
        $this->assertDatabaseCount('xml_nf_body', 2);
    }

    public function test_xml_item_number_cannot_repeat_in_the_same_document(): void
    {
        $header = $this->xmlHeader('1');
        $this->xmlItem($header, '1');

        $this->assertDatabaseError(1062, fn () => $this->xmlItem($header, '1'));
        $this->assertDatabaseCount('xml_nf_body', 1);
    }

    public function test_missing_xml_lot_and_dates_are_stored_as_null(): void
    {
        $item = $this->xmlItem($this->xmlHeader('1'), '1')->fresh();

        foreach (['nLote', 'qLote', 'dFab', 'dVal', 'cAgreg'] as $field) {
            $this->assertNull($item->$field, $field);
        }
    }

    public function test_reception_links_to_its_xml_item_and_preserves_both_on_delete_attempt(): void
    {
        $item = $this->xmlItem($this->xmlHeader('1'), '1');
        $row = $this->reception($item, '2.5000')->fresh();

        $this->assertTrue($row->xmlNfBody->is($item));
        $this->assertTrue($item->receptionBodies->sole()->is($row));
        $this->assertDatabaseError(1451, fn () => $item->delete());
        $this->assertDatabaseHas('xml_nf_body', ['id' => $item->id]);
        $this->assertDatabaseHas('reception_body', [
            'row' => $row->row, 'xml_nf_body_id' => $item->id,
        ]);
    }

    public function test_reception_cannot_reference_a_missing_xml_item(): void
    {
        $item = $this->xmlItem($this->xmlHeader('1'), '1');
        $row = $this->reception($item, '1.0000');

        $this->assertDatabaseError(1452, fn () => $row->update(['xml_nf_body_id' => $item->id + 1000]));
        $this->assertSame($item->id, $row->fresh()->xml_nf_body_id);
    }

    public function test_fractional_theoretical_quantity_is_preserved_and_legacy_link_is_nullable(): void
    {
        $fixture = $this->fixture();
        $item = $this->xmlItem($this->xmlHeader('1'), '1');
        $row = $this->reception($item, (string) $fixture->infNFe->det[0]->prod->qCom)->fresh();

        $this->assertSame('2.5000', $row->theoretical);
        $row->update(['theoretical' => '12345678901.1234', 'xml_nf_body_id' => null]);
        $row = $row->fresh();
        $this->assertSame('12345678901.1234', $row->theoretical);
        $this->assertNull($row->xmlNfBody);
        foreach (['quantity', 'stock_id', 'batch', 'fabrication', 'validity'] as $field) {
            $this->assertNull($row->$field, $field);
        }
        $row->update(['theoretical' => null]);
        $this->assertNull($row->fresh()->theoretical);
    }

    public function test_default_category_is_resolved_by_name_instead_of_numeric_id(): void
    {
        $name = config('nfe.default_category');
        $this->assertSame('default', $name);
        $category = Category::where('category', $name)->sole();
        DB::table('categories')->where('id', $category->id)->update(['id' => 42]);

        $this->assertSame(42, Category::where('category', config('nfe.default_category'))->sole()->id);
    }

    public function test_default_category_name_is_configurable(): void
    {
        DB::table('categories')->insert(['id' => 43, 'category' => 'nfe-test']);
        config(['nfe.default_category' => 'nfe-test']);

        $this->assertSame(43, Category::where('category', config('nfe.default_category'))->sole()->id);
    }

    public function test_synthetic_fixture_contains_two_items_and_optional_lot_variants(): void
    {
        $xml = $this->fixture();
        $this->assertSame('NFe', $xml->getName());
        $this->assertSame('http://www.portalfiscal.inf.br/nfe', $xml->getDocNamespaces()['']);
        $this->assertSame('4.00', (string) $xml->infNFe['versao']);
        $this->assertSame('NFe'.str_repeat('0', 43).'1', (string) $xml->infNFe['Id']);
        $this->assertCount(1, $xml->infNFe->ide);
        $this->assertSame('00000000000000', (string) $xml->infNFe->emit->CNPJ);
        $this->assertCount(1, $xml->infNFe->emit->enderEmit);
        $this->assertCount(1, $xml->infNFe->dest->enderDest);
        $this->assertCount(2, $xml->infNFe->det);
        foreach ($xml->infNFe->det as $index => $item) {
            foreach (['cProd', 'xProd', 'uCom', 'qCom', 'vUnCom', 'vProd'] as $field) {
                $this->assertNotSame('', trim((string) $item->prod->$field), $field);
            }
        }
        $this->assertSame('1', (string) $xml->infNFe->det[0]['nItem']);
        $this->assertSame('2', (string) $xml->infNFe->det[1]['nItem']);
        $this->assertSame('2.5000', (string) $xml->infNFe->det[0]->prod->qCom);
        foreach (['nLote', 'dFab', 'dVal'] as $field) {
            $this->assertCount(1, $xml->infNFe->det[0]->prod->$field);
            $this->assertCount(0, $xml->infNFe->det[1]->prod->$field);
        }
    }

    private function supplier(string $suffix): Supplier
    {
        return Supplier::create([
            'supplier' => 'Ficticio '.$suffix, 'address' => 'Endereco ficticio',
            'phone1' => '00000000000', 'email' => 'ficticio'.$suffix.'@example.test',
            'cnpj' => str_pad($suffix, 14, '0', STR_PAD_LEFT),
        ]);
    }

    private function product(string $id): Product
    {
        return Product::create([
            'id' => $id, 'description' => 'Produto ficticio',
            'category_id' => Category::where('category', config('nfe.default_category'))->sole()->id,
        ]);
    }

    private function mapping(Supplier $supplier, Product $product, string $code): SupplierProduct
    {
        return SupplierProduct::create([
            'supplier_id' => $supplier->id, 'supplier_product_code' => $code, 'product_id' => $product->id,
        ]);
    }

    private function xmlHeader(string $suffix): XmlNfHeader
    {
        return XmlNfHeader::create(['tipoNF' => 'compra', 'idnf' => 'FICTICIA-'.$suffix]);
    }

    private function xmlItem(XmlNfHeader $header, string $number): XmlNfBody
    {
        return XmlNfBody::create(['header' => $header->id, 'nItem' => $number, 'cProd' => 'FORN-001']);
    }

    private function reception(XmlNfBody $item, string $theoretical): ReceptionBody
    {
        $header = ReceptionHeader::create([
            'supplier_id' => $this->supplier('1')->id, 'xml_nf_header_id' => $item->header,
            'rows' => 1, 'status' => 'to receive',
        ]);

        return ReceptionBody::create([
            'header' => $header->id, 'product_id' => $this->product('INTERNAL-001')->id,
            'xml_nf_body_id' => $item->id, 'theoretical' => $theoretical,
        ]);
    }

    private function fixture(): SimpleXMLElement
    {
        $path = base_path('tests/Fixtures/nfe/inbound-ficticia.xml');
        $this->assertFileExists($path);

        return new SimpleXMLElement(file_get_contents($path), LIBXML_NONET);
    }

    private function assertDatabaseError(int $code, callable $operation): void
    {
        try {
            $operation();
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0]);
            $this->assertSame($code, $exception->errorInfo[1]);

            return;
        }

        $this->fail('MariaDB should have rejected the constraint violation.');
    }
}
