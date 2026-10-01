<?php

namespace Tests\Feature;

use App\Services\Nfe\NfeInboundDocument;
use App\Services\Nfe\NfeInboundParser;
use App\Services\Nfe\NfeInboundValidationException;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class NfeInboundParserTest extends TestCase
{
    private const NFE_NAMESPACE = 'http://www.portalfiscal.inf.br/nfe';

    public function test_synthetic_fixture_parses_to_a_normalized_typed_document(): void
    {
        $document = $this->parse($this->fixture());

        $this->assertInstanceOf(NfeInboundDocument::class, $document);
        $this->assertSame('NFe'.str_repeat('0', 43).'1', $document->id);
        $this->assertSame('4.00', $document->version);
        $this->assertSame('00000000000000', $document->supplierCnpj);
        $this->assertCount(2, $document->items);
        $this->assertSame('FORN-001', $document->items[0]->supplierProductCode);
        $this->assertSame('2.5000', $document->items[0]->commercialQuantity);
        $this->assertSame('10.0000000000', $document->items[0]->unitPrice);
        $this->assertSame('25.00', $document->items[0]->totalPrice);
    }

    public function test_nfe_with_direct_inf_nfe_is_supported(): void
    {
        $document = $this->parse($this->fixture());

        $this->assertSame(2, count($document->items));
    }

    public function test_nfe_proc_envelope_with_nfe_and_inf_nfe_is_supported(): void
    {
        $inner = $this->withoutXmlDeclaration($this->fixture());
        $xml = '<nfeProc xmlns="'.self::NFE_NAMESPACE.'">'.$inner.'</nfeProc>';

        $this->assertSame($this->parse($this->fixture())->id, $this->parse($xml)->id);
    }

    public function test_prefix_bound_to_the_official_namespace_is_supported(): void
    {
        $xml = $this->withoutXmlDeclaration($this->fixture());
        $xml = preg_replace('/(<\/?)([A-Za-z][A-Za-z0-9]*)(?=[\s>])/', '$1n:$2', $xml);
        $xml = str_replace('xmlns="'.self::NFE_NAMESPACE.'"', 'xmlns:n="'.self::NFE_NAMESPACE.'"', $xml);

        $this->assertSame($this->parse($this->fixture())->id, $this->parse($xml)->id);
    }

    public function test_malformed_xml_is_rejected_without_leaking_parser_warnings(): void
    {
        $this->assertRejected('<NFe><infNFe>');
    }

    public function test_missing_or_multiple_inf_nfe_are_rejected(): void
    {
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->remove($xpath, '/n:NFe/n:infNFe')));

        $this->assertRejected($this->editXml(function (DOMDocument $doc, DOMXPath $xpath): void {
            $infNfe = $xpath->query('/n:NFe/n:infNFe')->item(0);
            $doc->documentElement->appendChild($infNfe->cloneNode(true));
        }));
    }

    public function test_unsupported_namespace_and_structure_are_rejected(): void
    {
        $wrongNamespace = str_replace(self::NFE_NAMESPACE, 'urn:unsupported:nfe', $this->fixture());
        $this->assertRejected($wrongNamespace);
        $this->assertRejected('<Envelope>'.$this->withoutXmlDeclaration($this->fixture()).'</Envelope>');
    }

    public function test_doctype_is_rejected(): void
    {
        $xml = $this->fixture();
        $xml = preg_replace('/<\?xml[^>]*>\s*/', '', $xml, 1);

        $this->assertRejected('<!DOCTYPE NFe [<!ENTITY test "x">]>'.$xml);
    }

    public function test_configured_xml_size_limit_is_enforced(): void
    {
        config(['nfe.inbound.max_xml_bytes' => strlen($this->fixture()) - 1]);

        $this->assertRejected($this->fixture());
    }

    public function test_at_least_one_det_and_configured_maximum_are_required(): void
    {
        $this->assertRejected($this->editXml(function (DOMDocument $doc, DOMXPath $xpath): void {
            foreach ($xpath->query('/n:NFe/n:infNFe/n:det') as $det) {
                $det->parentNode->removeChild($det);
            }
        }));

        config(['nfe.inbound.max_items' => 1]);
        $this->assertRejected($this->fixture());
    }

    public function test_id_must_be_present_nonempty_and_fit_the_schema_format(): void
    {
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->attribute($xpath, '/n:NFe/n:infNFe', 'Id', null)));
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->attribute($xpath, '/n:NFe/n:infNFe', 'Id', '')));
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->attribute($xpath, '/n:NFe/n:infNFe', 'Id', 'not-an-nfe-id')));
    }

    public function test_emit_cnpj_is_required_and_normalized_to_fourteen_digits(): void
    {
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->remove($xpath, '/n:NFe/n:infNFe/n:emit/n:CNPJ')));
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:emit/n:CNPJ', '12.345.678/0001-9')));
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:emit/n:CNPJ', '12A34567800019')));

        $document = $this->parse($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:emit/n:CNPJ', '00.000.000/0000-00')));
        $this->assertSame('00000000000000', $document->supplierCnpj);
    }

    public function test_n_item_is_required_normalized_and_unique_within_the_document(): void
    {
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->attribute($xpath, '/n:NFe/n:infNFe/n:det[1]', 'nItem', null)));

        $normalized = $this->parse($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->attribute($xpath, '/n:NFe/n:infNFe/n:det[1]', 'nItem', '001')));
        $this->assertSame('1', $normalized->items[0]->nItem);

        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->attribute($xpath, '/n:NFe/n:infNFe/n:det[2]', 'nItem', '1')));
    }

    public function test_cprod_is_required_and_preserves_leading_zeroes(): void
    {
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->remove($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:cProd')));

        $document = $this->parse($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:cProd', '0000123')));
        $this->assertSame('0000123', $document->items[0]->supplierProductCode);
    }

    public function test_description_must_not_be_empty_or_whitespace_and_fit_the_column(): void
    {
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:xProd', '')));
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:xProd', '   ')));
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:xProd', str_repeat('x', 121))));
    }

    public function test_unit_is_required_and_rejected_if_it_would_be_truncated(): void
    {
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->remove($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:uCom')));
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:uCom', 'UNID5')));
    }

    public function test_quantity_preserves_fraction_and_rejects_zero_negative_or_excess_scale(): void
    {
        $fraction = $this->parse($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:qCom', '0002.1250')));
        $this->assertSame('2.1250', $fraction->items[0]->commercialQuantity);

        foreach (['0', '-1', '1.00000', '12345678901234567.1234'] as $invalid) {
            $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
                $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:qCom', $invalid)));
        }
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->remove($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:qCom')));
    }

    public function test_quantity_accepts_the_exact_decimal_20_4_upper_boundary(): void
    {
        $document = $this->parse($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:qCom', '1234567890123456.1234')));

        $this->assertSame('1234567890123456.1234', $document->items[0]->commercialQuantity);
    }

    public function test_unit_price_is_preserved_as_decimal_and_cannot_be_negative(): void
    {
        $document = $this->parse($this->fixture());
        $this->assertSame('10.0000000000', $document->items[0]->unitPrice);
        $boundary = $this->parse($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:vUnCom', '123456789012.1234567890')));
        $this->assertSame('123456789012.1234567890', $boundary->items[0]->unitPrice);

        foreach (['1234567890123.00', '1.12345678901'] as $invalid) {
            $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
                $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:vUnCom', $invalid)));
        }
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:vUnCom', '-0.01')));
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->remove($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:vUnCom')));
    }

    public function test_total_price_is_distinct_from_the_unit_price(): void
    {
        $document = $this->parse($this->fixture());

        $this->assertSame('10.0000000000', $document->items[0]->unitPrice);
        $this->assertSame('25.00', $document->items[0]->totalPrice);
        $this->assertNotSame($document->items[0]->unitPrice, $document->items[0]->totalPrice);
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->remove($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:vProd')));
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:vProd', '-0.01')));
    }

    public function test_missing_lot_and_dates_are_null_and_iso_dates_are_preserved(): void
    {
        $document = $this->parse($this->fixture());
        $unbatched = $document->items[1];
        $this->assertNull($unbatched->batch);
        $this->assertNull($unbatched->batchQuantity);
        $this->assertNull($unbatched->manufactureDate);
        $this->assertNull($unbatched->expiryDate);
        $this->assertNull($unbatched->aggregationCode);

        $batched = $document->items[0];
        $this->assertSame('2026-09-01', $batched->manufactureDate);
        $this->assertSame('2027-09-01', $batched->expiryDate);
    }

    public function test_impossible_and_non_iso_dates_are_rejected(): void
    {
        foreach (['2026-02-30', '01/09/2026'] as $invalid) {
            $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
                $this->text($xpath, '/n:NFe/n:infNFe/n:det[1]/n:prod/n:dFab', $invalid)));
        }
    }

    public function test_supported_version_is_extracted_and_unknown_version_is_rejected(): void
    {
        $this->assertRejected($this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->attribute($xpath, '/n:NFe/n:infNFe', 'versao', '3.10')));

        $withoutVersion = $this->editXml(fn (DOMDocument $doc, DOMXPath $xpath) =>
            $this->attribute($xpath, '/n:NFe/n:infNFe', 'versao', null));
        $this->assertNull($this->parse($withoutVersion)->version);
    }

    private function parse(string $xml): NfeInboundDocument
    {
        return (new NfeInboundParser())->parse($xml);
    }

    private function assertRejected(string $xml): void
    {
        try {
            $this->parse($xml);
        } catch (NfeInboundValidationException $exception) {
            $this->assertNotSame('', $exception->getMessage());

            return;
        }

        $this->fail('Expected inbound NF-e validation to reject the XML.');
    }

    private function fixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/nfe/inbound-ficticia.xml'));
    }

    private function withoutXmlDeclaration(string $xml): string
    {
        return preg_replace('/<\?xml[^>]*>\s*/', '', $xml, 1);
    }

    private function editXml(callable $edit): string
    {
        $document = new DOMDocument();
        $document->loadXML($this->fixture(), LIBXML_NONET);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('n', self::NFE_NAMESPACE);
        $edit($document, $xpath);

        return $document->saveXML();
    }

    private function remove(DOMXPath $xpath, string $expression): void
    {
        $node = $xpath->query($expression)->item(0);
        $node->parentNode->removeChild($node);
    }

    private function text(DOMXPath $xpath, string $expression, string $value): void
    {
        $xpath->query($expression)->item(0)->textContent = $value;
    }

    private function attribute(DOMXPath $xpath, string $expression, string $name, ?string $value): void
    {
        $element = $xpath->query($expression)->item(0);
        if ($value === null) {
            $element->removeAttribute($name);
        } else {
            $element->setAttribute($name, $value);
        }
    }
}
