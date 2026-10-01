<?php

namespace App\Services\Nfe;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Throwable;

final class NfeInboundParser
{
    private const NFE_NAMESPACE = 'http://www.portalfiscal.inf.br/nfe';

    public function parse(string $xmlContent): NfeInboundDocument
    {
        $maxBytes = (int) config('nfe.inbound.max_xml_bytes', 2 * 1024 * 1024);
        if ($maxBytes < 1 || strlen($xmlContent) > $maxBytes) {
            throw new NfeInboundValidationException('XML excede o tamanho máximo permitido.');
        }

        $document = $this->loadXml($xmlContent);
        if ($document->doctype !== null) {
            throw new NfeInboundValidationException('DTD/DOCTYPE não é suportado.');
        }

        $root = $document->documentElement;
        if (!$root instanceof DOMElement || $root->namespaceURI !== self::NFE_NAMESPACE) {
            throw new NfeInboundValidationException('Raiz XML ou namespace da NF-e não suportado.');
        }

        $xpath = new DOMXPath($document);
        $allInfNfe = $xpath->query('//*[local-name()="infNFe"]');
        if ($allInfNfe === false || $allInfNfe->length === 0) {
            throw new NfeInboundValidationException('Documento não contém infNFe.');
        }
        if ($allInfNfe->length !== 1) {
            throw new NfeInboundValidationException('Documento deve conter exatamente um infNFe.');
        }

        if ($root->localName === 'NFe') {
            $infNfe = $this->singleChild($root, 'infNFe', true);
        } elseif ($root->localName === 'nfeProc') {
            $nfe = $this->singleChild($root, 'NFe', true);
            $infNfe = $this->singleChild($nfe, 'infNFe', true);
        } else {
            throw new NfeInboundValidationException('Estrutura XML inbound não suportada.');
        }

        $id = trim($infNfe->getAttribute('Id'));
        if ($id === '' || strlen($id) > 47 || preg_match('/^NFe[0-9]{44}$/D', $id) !== 1) {
            throw new NfeInboundValidationException('infNFe/@Id ausente ou incompatível com o schema do projeto.');
        }

        $version = null;
        if ($infNfe->hasAttribute('versao')) {
            $version = trim($infNfe->getAttribute('versao'));
            if ($version !== '4.00') {
                throw new NfeInboundValidationException('Versão de leiaute NF-e não suportada nesta rodada.');
            }
        }

        $emit = $this->singleChild($infNfe, 'emit', true);
        $cnpjText = $this->requiredText($emit, 'CNPJ');
        if (preg_match('/^[0-9.\/-]+$/D', $cnpjText) !== 1) {
            throw new NfeInboundValidationException('CNPJ do emitente possui formato inválido.');
        }
        $supplierCnpj = preg_replace('/[.\/-]/', '', $cnpjText);
        if ($supplierCnpj === null || preg_match('/^[0-9]{14}$/D', $supplierCnpj) !== 1) {
            throw new NfeInboundValidationException('CNPJ do emitente deve conter exatamente 14 dígitos.');
        }

        $detNodes = $this->childrenNamed($infNfe, 'det');
        $maxItems = (int) config('nfe.inbound.max_items', 990);
        if ($detNodes === []) {
            throw new NfeInboundValidationException('A NF-e deve conter ao menos um det.');
        }
        if ($maxItems < 1 || count($detNodes) > $maxItems) {
            throw new NfeInboundValidationException('Quantidade de itens det excede o limite permitido.');
        }

        $items = [];
        $seenItemNumbers = [];
        foreach ($detNodes as $det) {
            $nItem = $this->normalizeItemNumber($det->getAttribute('nItem'));
            if (isset($seenItemNumbers[$nItem])) {
                throw new NfeInboundValidationException("nItem duplicado: {$nItem}.");
            }
            $seenItemNumbers[$nItem] = true;

            $product = $this->singleChild($det, 'prod', true);
            $code = $this->requiredText($product, 'cProd');
            $this->assertLength($code, 60, 'cProd');
            $description = $this->requiredText($product, 'xProd');
            $this->assertLength($description, 120, 'xProd');
            $unit = $this->requiredText($product, 'uCom');
            // Product.unit is VARCHAR(4); reject now rather than truncate in 2B-3.
            $this->assertLength($unit, 4, 'uCom');

            $items[] = new NfeInboundItem(
                nItem: $nItem,
                supplierProductCode: $code,
                description: $description,
                unit: $unit,
                commercialQuantity: $this->decimal(
                    $this->requiredText($product, 'qCom'), 'qCom', null, true, 4, 16
                ),
                unitPrice: $this->decimal(
                    $this->requiredText($product, 'vUnCom'), 'vUnCom', 23, false, 10, 12
                ),
                totalPrice: $this->decimal(
                    $this->requiredText($product, 'vProd'), 'vProd', 16, false
                ),
                batch: $this->optionalText($product, 'nLote', 20),
                batchQuantity: $this->optionalText($product, 'qLote', 12),
                manufactureDate: $this->optionalDate($product, 'dFab'),
                expiryDate: $this->optionalDate($product, 'dVal'),
                aggregationCode: $this->optionalText($product, 'cAgreg', 20),
            );
        }

        return new NfeInboundDocument($id, $version, $supplierCnpj, $items);
    }

    private function loadXml(string $xmlContent): DOMDocument
    {
        $previousErrorMode = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $document = new DOMDocument();
            $loaded = @$document->loadXML($xmlContent, LIBXML_NONET);
            if (!$loaded) {
                throw new NfeInboundValidationException('XML malformado ou inválido.');
            }

            return $document;
        } catch (NfeInboundValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new NfeInboundValidationException('Falha controlada ao interpretar XML.', 0, $exception);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }
    }

    /** @return list<DOMElement> */
    private function childrenNamed(DOMElement $parent, string $name): array
    {
        $matches = [];
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $name) {
                if ($child->namespaceURI !== self::NFE_NAMESPACE) {
                    throw new NfeInboundValidationException("Namespace incompatível no elemento {$name}.");
                }
                $matches[] = $child;
            }
        }

        return $matches;
    }

    private function singleChild(DOMElement $parent, string $name, bool $required): ?DOMElement
    {
        $matches = $this->childrenNamed($parent, $name);
        if (count($matches) > 1 || ($required && count($matches) !== 1)) {
            throw new NfeInboundValidationException("Estrutura ausente ou ambígua no elemento {$name}.");
        }

        return $matches[0] ?? null;
    }

    private function requiredText(DOMElement $parent, string $name): string
    {
        $element = $this->singleChild($parent, $name, true);
        $value = trim($element->textContent);
        if ($value === '') {
            throw new NfeInboundValidationException("Campo obrigatório vazio: {$name}.");
        }

        return $value;
    }

    private function optionalText(DOMElement $parent, string $name, int $maxLength): ?string
    {
        $element = $this->singleChild($parent, $name, false);
        $value = $element === null ? null : trim($element->textContent);
        if ($value === null || $value === '') {
            return null;
        }
        $this->assertLength($value, $maxLength, $name);

        return $value;
    }

    private function optionalDate(DOMElement $parent, string $name): ?string
    {
        $value = $this->optionalText($parent, $name, 10);
        if ($value === null) {
            return null;
        }
        if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $value, $parts) !== 1
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new NfeInboundValidationException("Data inválida ou formato não suportado em {$name}.");
        }

        return $value;
    }

    private function normalizeItemNumber(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^[0-9]+$/D', $value) !== 1) {
            throw new NfeInboundValidationException('nItem ausente ou inválido.');
        }
        $normalized = ltrim($value, '0');
        $normalized = $normalized === '' ? '0' : $normalized;
        if ($normalized === '0' || strlen($normalized) > 3) {
            throw new NfeInboundValidationException('nItem deve caber no limite de 3 caracteres.');
        }

        return $normalized;
    }

    private function decimal(
        string $value,
        string $field,
        ?int $maxLength,
        bool $mustBePositive,
        ?int $maxScale = null,
        ?int $maxIntegerDigits = null,
    ): string {
        if (preg_match('/^[+-]?(?:[0-9]+(?:\.[0-9]+)?|\.[0-9]+)$/D', $value) !== 1) {
            throw new NfeInboundValidationException("{$field} deve ser um decimal sem notação científica.");
        }

        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '+-');
        [$integer, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;
        if (strlen($integer) > ($maxIntegerDigits ?? $maxLength)
            || ($maxScale !== null && strlen($fraction) > $maxScale)) {
            throw new NfeInboundValidationException("{$field} excede a precisão/escala suportada.");
        }

        $isZero = $integer === '0' && ($fraction === '' || trim($fraction, '0') === '');
        if ($mustBePositive && ($negative || $isZero)) {
            throw new NfeInboundValidationException("{$field} deve ser maior que zero.");
        }
        if (!$mustBePositive && $negative && !$isZero) {
            throw new NfeInboundValidationException("{$field} não pode ser negativo.");
        }

        $normalized = ($negative && !$isZero ? '-' : '').$integer.($fraction !== '' ? '.'.$fraction : '');
        if ($maxLength !== null && strlen($normalized) > $maxLength) {
            throw new NfeInboundValidationException("{$field} excede o limite de armazenamento suportado.");
        }

        return $normalized;
    }

    private function assertLength(string $value, int $maxLength, string $field): void
    {
        $matched = preg_match_all('/./us', $value, $characters);
        $length = $matched === false ? strlen($value) : $matched;
        if ($length > $maxLength) {
            throw new NfeInboundValidationException("{$field} excede o limite de {$maxLength} caracteres.");
        }
    }
}
