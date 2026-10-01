<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ReceptionBody;
use App\Models\ReceptionHeader;
use App\Models\Supplier;
use App\Models\XmlNfBody;
use App\Models\XmlNfHeader;
use App\Services\Nfe\NfeInboundImportCompleteness;
use App\Services\Nfe\NfeInboundImporter;
use App\Services\Nfe\NfeInboundParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\DatabaseTestCase;

class NfeInboundLifecycleTest extends DatabaseTestCase
{
    public function test_committed_import_is_archived_and_source_is_removed(): void
    {
        $this->supplier();
        Storage::fake('local');
        $source = $this->inbound('committed.xml');

        $this->artisan('nfe:process')->assertExitCode(0);

        Storage::disk('local')->assertMissing($source);
        Storage::disk('local')->assertExists('xml/nfs/processado/inbound/committed.xml');
        $this->assertDatabaseCount('xml_nf_header', 1);
        $this->assertDatabaseCount('xml_nf_body', 2);
        $this->assertDatabaseCount('reception_headers', 1);
        $this->assertDatabaseCount('reception_body', 2);
    }

    public function test_supplier_failure_keeps_source_inbound_without_partial_database_rows(): void
    {
        Storage::fake('local');
        $source = $this->inbound('supplier-missing.xml');

        $this->artisan('nfe:process')->assertExitCode(0);

        Storage::disk('local')->assertExists($source);
        Storage::disk('local')->assertMissing('xml/nfs/processado/inbound/supplier-missing.xml');
        $this->assertNoImportRecords();
    }

    public function test_invalid_default_category_keeps_source_and_rolls_back(): void
    {
        $this->supplier();
        Category::query()->where('category', config('nfe.default_category'))->delete();
        Storage::fake('local');
        $source = $this->inbound('category-missing.xml');

        $this->artisan('nfe:process')->assertExitCode(0);

        Storage::disk('local')->assertExists($source);
        Storage::disk('local')->assertMissing('xml/nfs/processado/inbound/category-missing.xml');
        $this->assertNoImportRecords();
    }

    public function test_importer_failure_before_commit_keeps_source_and_rolls_back(): void
    {
        $this->supplier();
        Storage::fake('local');
        $source = $this->inbound('importer-failure.xml');
        \App\Models\Product::creating(function (): void {
            throw new RuntimeException('Injected pre-commit importer failure.');
        });

        $this->artisan('nfe:process')->assertExitCode(0);

        Storage::disk('local')->assertExists($source);
        Storage::disk('local')->assertMissing('xml/nfs/processado/inbound/importer-failure.xml');
        $this->assertNoImportRecords();
    }

    public function test_archive_conflict_keeps_committed_import_and_retry_only_archives(): void
    {
        $this->supplier();
        Storage::fake('local');
        $source = $this->inbound('retry.xml');
        $destination = 'xml/nfs/processado/inbound/retry.xml';
        Storage::disk('local')->put($destination, 'pre-existing processed file');

        $this->artisan('nfe:process')->assertExitCode(0);

        Storage::disk('local')->assertExists($source);
        $this->assertSame('pre-existing processed file', Storage::disk('local')->get($destination));
        $this->assertDatabaseCount('xml_nf_header', 1);
        $this->assertDatabaseCount('xml_nf_body', 2);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('supplier_products', 2);
        $this->assertDatabaseCount('reception_headers', 1);
        $this->assertDatabaseCount('reception_body', 2);

        Storage::disk('local')->delete($destination);
        $this->artisan('nfe:process')->assertExitCode(0);

        Storage::disk('local')->assertMissing($source);
        Storage::disk('local')->assertExists($destination);
        $this->assertSame($this->fixture(), Storage::disk('local')->get($destination));
        $this->assertDatabaseCount('xml_nf_header', 1);
        $this->assertDatabaseCount('xml_nf_body', 2);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('supplier_products', 2);
        $this->assertDatabaseCount('reception_headers', 1);
        $this->assertDatabaseCount('reception_body', 2);
    }

    public function test_same_nfe_in_a_second_file_is_archived_without_reimport(): void
    {
        $this->supplier();
        Storage::fake('local');
        $firstSource = $this->inbound('first-copy.xml');
        $this->artisan('nfe:process')->assertExitCode(0);
        Storage::disk('local')->assertMissing($firstSource);

        $secondSource = $this->inbound('second-copy.xml');
        $this->artisan('nfe:process')->assertExitCode(0);

        Storage::disk('local')->assertMissing($secondSource);
        Storage::disk('local')->assertExists('xml/nfs/processado/inbound/second-copy.xml');
        $this->assertDatabaseCount('xml_nf_header', 1);
        $this->assertDatabaseCount('xml_nf_body', 2);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('supplier_products', 2);
        $this->assertDatabaseCount('reception_headers', 1);
        $this->assertDatabaseCount('reception_body', 2);
    }

    public function test_duplicate_with_incomplete_existing_import_is_not_archived(): void
    {
        $this->supplier();
        $document = (new NfeInboundParser())->parse($this->fixture());
        (new NfeInboundImporter())->import($document);
        $receptionBody = ReceptionBody::query()->firstOrFail();
        $receptionBody->delete();

        Storage::fake('local');
        $source = $this->inbound('incomplete-duplicate.xml');
        $this->artisan('nfe:process')->assertExitCode(0);

        Storage::disk('local')->assertExists($source);
        Storage::disk('local')->assertMissing('xml/nfs/processado/inbound/incomplete-duplicate.xml');
        $this->assertDatabaseCount('xml_nf_header', 1);
        $this->assertDatabaseCount('xml_nf_body', 2);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('supplier_products', 2);
        $this->assertDatabaseCount('reception_headers', 1);
        $this->assertDatabaseCount('reception_body', 1);
    }

    public function test_same_file_is_skipped_while_its_filesystem_lock_is_held(): void
    {
        $this->supplier();
        Storage::fake('local');
        $source = $this->inbound('locked.xml');
        $lockPath = Storage::disk('local')->path(
            'xml/nfs/inbound/.locks/'.hash('sha256', $source).'.lock'
        );
        mkdir(dirname($lockPath), 0775, true);
        $handle = fopen($lockPath, 'c');
        $this->assertNotFalse($handle);
        $this->assertTrue(flock($handle, LOCK_EX | LOCK_NB));

        $this->artisan('nfe:process')->assertExitCode(0);
        Storage::disk('local')->assertExists($source);
        $this->assertNoImportRecords();

        flock($handle, LOCK_UN);
        fclose($handle);
        $this->artisan('nfe:process')->assertExitCode(0);
        Storage::disk('local')->assertMissing($source);
        $this->assertDatabaseCount('xml_nf_header', 1);
    }

    public function test_import_completeness_requires_a_reception_header(): void
    {
        $this->supplier();
        $document = (new NfeInboundParser())->parse($this->fixture());
        (new NfeInboundImporter())->import($document);
        ReceptionHeader::query()->where('xml_nf_header_id', XmlNfHeader::query()->sole()->id)->delete();

        $this->assertFalse((new NfeInboundImportCompleteness())->isComplete($document));
    }

    public function test_import_completeness_requires_every_expected_xml_item(): void
    {
        $this->supplier();
        $document = (new NfeInboundParser())->parse($this->fixture());
        (new NfeInboundImporter())->import($document);
        $item = XmlNfBody::query()->where('nItem', '2')->sole();
        ReceptionBody::query()->where('xml_nf_body_id', $item->id)->delete();
        $item->delete();

        $this->assertFalse((new NfeInboundImportCompleteness())->isComplete($document));
    }

    public function test_import_completeness_requires_one_reception_body_per_xml_item(): void
    {
        $this->supplier();
        $document = (new NfeInboundParser())->parse($this->fixture());
        (new NfeInboundImporter())->import($document);
        ReceptionBody::query()->where('xml_nf_body_id', XmlNfBody::query()->where('nItem', '2')->sole()->id)->delete();

        $this->assertFalse((new NfeInboundImportCompleteness())->isComplete($document));
    }

    public function test_complete_import_matches_expected_items_and_receipt_links(): void
    {
        $this->supplier();
        $document = (new NfeInboundParser())->parse($this->fixture());
        (new NfeInboundImporter())->import($document);

        $this->assertTrue((new NfeInboundImportCompleteness())->isComplete($document));
    }

    private function fixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/nfe/inbound-ficticia.xml'));
    }

    private function inbound(string $filename): string
    {
        $path = 'xml/nfs/inbound/'.$filename;
        Storage::disk('local')->put($path, $this->fixture());

        return $path;
    }

    private function supplier(): Supplier
    {
        return Supplier::query()->create([
            'supplier' => 'Ficticio',
            'address' => 'Endereco ficticio',
            'phone1' => '00000000000',
            'email' => 'nfe-lifecycle@example.test',
            'cnpj' => '00000000000000',
        ]);
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
