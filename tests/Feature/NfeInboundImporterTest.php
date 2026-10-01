<?php

namespace Tests\Feature;

use App\Console\Commands\ProcessNFeXml;
use App\Models\Category;
use App\Models\Product;
use App\Models\ReceptionBody;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\XmlNfBody;
use App\Models\XmlNfHeader;
use App\Services\Nfe\NfeInboundDuplicateException;
use App\Services\Nfe\NfeInboundImportException;
use App\Services\Nfe\NfeInboundImporter;
use App\Services\Nfe\NfeInboundParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\DatabaseTestCase;

class NfeInboundImporterTest extends DatabaseTestCase
{
    public function test_fixture_import_persists_invoice_items_products_and_receipt_links(): void
    {
        $supplier = $this->supplier('00000000000000');
        $result = $this->import($this->fixture());

        $this->assertSame('NFe'.str_repeat('0', 43).'1', $result->xmlHeader->idnf);
        $this->assertSame('4.00', $result->xmlHeader->versao);
        $this->assertSame($supplier->id, $result->receptionHeader->supplier_id);
        $this->assertDatabaseCount('xml_nf_header', 1);
        $this->assertDatabaseCount('xml_nf_body', 2);
        $this->assertDatabaseCount('reception_headers', 1);
        $this->assertDatabaseCount('reception_body', 2);

        $firstItem = XmlNfBody::query()->where('nItem', '1')->sole();
        $firstReception = ReceptionBody::query()->where('xml_nf_body_id', $firstItem->id)->sole();
        $this->assertSame($result->xmlHeader->id, $firstItem->header);
        $this->assertSame('FORN-001', $firstItem->cProd);
        $this->assertSame('2.5000', $firstItem->qCom);
        $this->assertSame('10.0000000000', $firstItem->vUnCom);
        $this->assertSame('25.00', $firstItem->vProd);
        $this->assertSame('2.5000', $firstReception->theoretical);
        $this->assertSame($result->receptionHeader->id, $firstReception->header);
        $this->assertSame($firstItem->id, $firstReception->xml_nf_body_id);
        $this->assertSame('2026-09-01', $firstReception->fabrication);
        $this->assertSame('2027-09-01', $firstReception->validity);
        $this->assertSame('LOTE-FICTICIO-01', $firstReception->batch);

        $unbatched = XmlNfBody::query()->where('nItem', '2')->sole();
        $this->assertNull($unbatched->nLote);
        $this->assertNull($unbatched->qLote);
        $this->assertNull($unbatched->dFab);
        $this->assertNull($unbatched->dVal);
        $this->assertNull($unbatched->cAgreg);
        $unbatchedReception = ReceptionBody::query()->where('xml_nf_body_id', $unbatched->id)->sole();
        $this->assertNull($unbatchedReception->batch);
        $this->assertNull($unbatchedReception->fabrication);
        $this->assertNull($unbatchedReception->validity);
    }

    public function test_supplier_is_resolved_by_normalized_cnpj(): void
    {
        $supplier = $this->supplier('00.000.000/0000-00');
        $result = $this->import($this->fixture());

        $this->assertSame($supplier->id, $result->receptionHeader->supplier_id);
    }

    public function test_missing_or_ambiguous_supplier_fails_before_persistence(): void
    {
        try {
            $this->import($this->fixture());
            $this->fail('An unknown supplier must be rejected.');
        } catch (NfeInboundImportException $exception) {
            $this->assertStringContainsString('não encontrado', $exception->getMessage());
        }
        $this->assertNoImportRecords();

        $this->supplier('00000000000000');
        $this->supplier('00.000.000/0000-00');
        try {
            $this->import($this->fixture());
            $this->fail('Ambiguous normalized supplier CNPJ must be rejected.');
        } catch (NfeInboundImportException $exception) {
            $this->assertStringContainsString('ambíguo', $exception->getMessage());
        }
        $this->assertNoImportRecords();
    }

    public function test_default_category_is_resolved_by_name_not_a_fixed_id(): void
    {
        $supplier = $this->supplier('00000000000000');
        $category = Category::query()->where('category', config('nfe.default_category'))->sole();
        DB::table('categories')->where('id', $category->id)->update(['id' => 42]);

        $this->import($this->fixture());

        $this->assertSame(2, Product::query()->count());
        foreach (Product::query()->get() as $product) {
            $this->assertSame(42, (int) $product->category_id);
            $this->assertSame($supplier->id, $product->supplier_default_id);
        }
    }

    public function test_missing_or_ambiguous_default_category_fails_atomically(): void
    {
        $this->supplier('00000000000000');
        Category::query()->where('category', config('nfe.default_category'))->delete();
        $this->assertImportFailsAtomically('não encontrada');

        DB::table('categories')->insert(['category' => config('nfe.default_category')]);
        DB::table('categories')->insert(['category' => config('nfe.default_category')]);
        $this->assertImportFailsAtomically('ambígua');
    }

    public function test_missing_mapping_creates_separate_internal_product_and_mapping(): void
    {
        $supplier = $this->supplier('00000000000000');
        $this->import($this->fixture());

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('supplier_products', 2);
        foreach (SupplierProduct::query()->where('supplier_id', $supplier->id)->get() as $mapping) {
            $this->assertNotSame($mapping->supplier_product_code, $mapping->product_id);
            $this->assertSame(20, strlen($mapping->product_id));
            $this->assertTrue(Product::query()->whereKey($mapping->product_id)->exists());
        }

        $firstProduct = SupplierProduct::query()
            ->where('supplier_id', $supplier->id)
            ->where('supplier_product_code', 'FORN-001')
            ->sole()->product;
        $this->assertSame('PRODUTO FICTICIO FRACIONARIO', $firstProduct->description);
        $this->assertSame('KG', $firstProduct->unit);
        $this->assertSame('10.0000000000', $firstProduct->purchase_price);
        $this->assertNotSame('25.00', $firstProduct->purchase_price);
    }

    public function test_maximum_fractional_quantity_and_unit_price_persist_without_precision_loss(): void
    {
        $this->supplier('00000000000000');
        $xml = str_replace('<qCom>2.5000</qCom>', '<qCom>1234567890123456.1234</qCom>', $this->fixture());
        $xml = str_replace(
            '<vUnCom>10.0000000000</vUnCom>',
            '<vUnCom>123456789012.1234567890</vUnCom>',
            $xml,
        );

        $this->import($xml);

        $xmlItem = XmlNfBody::query()->where('nItem', '1')->sole();
        $receptionBody = ReceptionBody::query()->where('xml_nf_body_id', $xmlItem->id)->sole();
        $product = SupplierProduct::query()->where('supplier_product_code', 'FORN-001')->sole()->product;
        $this->assertSame('1234567890123456.1234', $xmlItem->qCom);
        $this->assertSame('1234567890123456.1234', $receptionBody->theoretical);
        $this->assertSame('123456789012.1234567890', $xmlItem->vUnCom);
        $this->assertSame('123456789012.1234567890', $product->purchase_price);
    }

    public function test_same_external_code_for_two_suppliers_maps_to_distinct_products(): void
    {
        $supplierA = $this->supplier('00000000000000');
        $supplierB = $this->supplier('11111111111111');
        $this->import($this->fixture());
        $this->import($this->fixture('2', '11111111111111'));

        $mappingA = SupplierProduct::query()
            ->where('supplier_id', $supplierA->id)->where('supplier_product_code', 'FORN-001')->sole();
        $mappingB = SupplierProduct::query()
            ->where('supplier_id', $supplierB->id)->where('supplier_product_code', 'FORN-001')->sole();
        $this->assertNotSame($mappingA->product_id, $mappingB->product_id);
        $this->assertSame(4, Product::query()->count());
    }

    public function test_existing_mapping_reuses_product_without_changing_its_fields_or_price(): void
    {
        $supplier = $this->supplier('00000000000000');
        $category = Category::query()->where('category', config('nfe.default_category'))->sole();
        $product = Product::query()->create([
            'id' => 'INTERNAL-EXISTING',
            'description' => 'Description kept as-is',
            'unit' => 'PC',
            'purchase_price' => '77.25',
            'category_id' => $category->id,
        ]);
        SupplierProduct::query()->create([
            'supplier_id' => $supplier->id,
            'supplier_product_code' => 'FORN-001',
            'product_id' => $product->id,
        ]);

        $this->import($this->fixture());

        $product->refresh();
        $this->assertSame('Description kept as-is', $product->description);
        $this->assertSame('PC', $product->unit);
        $this->assertSame('77.2500000000', $product->purchase_price);
        $this->assertSame($product->id, ReceptionBody::query()
            ->where('xml_nf_body_id', XmlNfBody::query()->where('nItem', '1')->sole()->id)
            ->sole()->product_id);
        $this->assertDatabaseCount('supplier_products', 2);
    }

    public function test_duplicate_document_is_rejected_without_creating_or_changing_records(): void
    {
        $this->supplier('00000000000000');
        $this->import($this->fixture());
        Product::query()->where('description', 'PRODUTO FICTICIO FRACIONARIO')
            ->update(['purchase_price' => '99.1234567890']);

        try {
            $this->import($this->fixture());
            $this->fail('A previously imported NF-e must be rejected.');
        } catch (NfeInboundDuplicateException $exception) {
            $this->assertStringContainsString('já importada', $exception->getMessage());
        }

        $this->assertDatabaseCount('xml_nf_header', 1);
        $this->assertDatabaseCount('xml_nf_body', 2);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('supplier_products', 2);
        $this->assertDatabaseCount('reception_headers', 1);
        $this->assertDatabaseCount('reception_body', 2);
        $this->assertSame('99.1234567890', Product::query()
            ->where('description', 'PRODUTO FICTICIO FRACIONARIO')->sole()->purchase_price);
    }

    public function test_mid_import_failure_rolls_back_every_database_write(): void
    {
        $this->supplier('00000000000000');
        $writesWereVisibleBeforeFailure = false;
        ReceptionBody::creating(function (): void {
            $this->assertDatabaseCount('xml_nf_header', 1);
            $this->assertDatabaseCount('xml_nf_body', 2);
            $this->assertDatabaseCount('products', 2);
            $this->assertDatabaseCount('supplier_products', 2);
            $this->assertDatabaseCount('reception_headers', 1);
            throw new RuntimeException('Injected failure after persistence has begun.');
        });

        try {
            $this->import($this->fixture());
            $this->fail('The injected failure must escape the importer.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected failure after persistence has begun.', $exception->getMessage());
            $writesWereVisibleBeforeFailure = true;
        }

        $this->assertTrue($writesWereVisibleBeforeFailure);
        $this->assertNoImportRecords();
    }

    public function test_invalid_xml_is_rejected_before_any_import_write(): void
    {
        $this->supplier('00000000000000');
        $path = 'xml/nfs/inbound/malformed.xml';
        Storage::fake('local');
        Storage::put($path, '<NFe><infNFe>');

        $this->artisan('nfe:process')->assertExitCode(0);

        Storage::assertExists($path);
        $this->assertNoImportRecords();
    }

    public function test_successful_command_import_keeps_file_in_inbound_for_round_2b_4(): void
    {
        $this->supplier('00000000000000');
        $path = 'xml/nfs/inbound/ficticia.xml';
        Storage::fake('local');
        Storage::put($path, $this->fixture());

        $this->artisan('nfe:process')->assertExitCode(0);

        Storage::assertExists($path);
        Storage::assertMissing('xml/nfs/processado/inbound/ficticia.xml');
        $this->assertDatabaseCount('xml_nf_header', 1);
    }

    private function import(string $xml)
    {
        $document = (new NfeInboundParser())->parse($xml);

        return (new NfeInboundImporter())->import($document);
    }

    private function fixture(?string $idSuffix = null, ?string $cnpj = null): string
    {
        $xml = file_get_contents(base_path('tests/Fixtures/nfe/inbound-ficticia.xml'));
        if ($idSuffix !== null) {
            $xml = str_replace(
                'NFe'.str_repeat('0', 43).'1',
                'NFe'.str_repeat('0', 43).$idSuffix,
                $xml,
            );
        }
        if ($cnpj !== null) {
            $xml = str_replace('<CNPJ>00000000000000</CNPJ>', '<CNPJ>'.$cnpj.'</CNPJ>', $xml);
        }

        return $xml;
    }

    private function supplier(string $cnpj): Supplier
    {
        return Supplier::query()->create([
            'supplier' => 'Ficticio',
            'address' => 'Endereco ficticio',
            'phone1' => '00000000000',
            'email' => 'ficticio'.str_replace(['.', '/', '-'], '', $cnpj).count(Supplier::all()).'@example.test',
            'cnpj' => $cnpj,
        ]);
    }

    private function assertImportFailsAtomically(string $message): void
    {
        try {
            $this->import($this->fixture());
            $this->fail('The invalid default category must reject the import.');
        } catch (NfeInboundImportException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
        $this->assertNoImportRecords();
    }

    private function assertNoImportRecords(): void
    {
        foreach ([
            'xml_nf_header', 'xml_nf_body', 'products', 'supplier_products',
            'reception_headers', 'reception_body',
        ] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }
}
