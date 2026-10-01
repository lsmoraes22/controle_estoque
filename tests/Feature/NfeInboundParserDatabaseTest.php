<?php

namespace Tests\Feature;

use App\Services\Nfe\NfeInboundParser;
use Tests\DatabaseTestCase;

class NfeInboundParserDatabaseTest extends DatabaseTestCase
{
    public function test_parsing_has_no_domain_database_side_effects(): void
    {
        $xml = file_get_contents(base_path('tests/Fixtures/nfe/inbound-ficticia.xml'));
        $document = (new NfeInboundParser())->parse($xml);

        $this->assertCount(2, $document->items);
        foreach ([
            'xml_nf_header', 'xml_nf_body', 'products', 'supplier_products',
            'reception_headers', 'reception_body', 'stock', 'journals',
        ] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }
}
