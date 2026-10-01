<?php

namespace App\Services\Nfe;

use App\Models\Category;
use App\Models\Product;
use App\Models\ReceptionBody;
use App\Models\ReceptionHeader;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\XmlNfBody;
use App\Models\XmlNfHeader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class NfeInboundImporter
{
    public function import(NfeInboundDocument $document): NfeInboundImportResult
    {
        return DB::transaction(function () use ($document): NfeInboundImportResult {
            if (XmlNfHeader::query()->where('idnf', $document->id)->exists()) {
                throw new NfeInboundDuplicateException('NF-e inbound já importada: '.$document->id);
            }

            $supplier = $this->resolveSupplier($document->supplierCnpj);
            $category = $this->resolveDefaultCategory();
            $productsByCode = $this->resolveProducts($document, $supplier, $category);

            $xmlHeader = XmlNfHeader::query()->create([
                'tipoNF' => 'compra',
                'idnf' => $document->id,
                'versao' => $document->version,
                'emitCNPJ' => $document->supplierCnpj,
                'valid' => true,
            ]);

            $xmlItems = [];
            foreach ($document->items as $item) {
                $xmlItems[$item->nItem] = XmlNfBody::query()->create([
                    'header' => $xmlHeader->id,
                    'nItem' => $item->nItem,
                    'cProd' => $item->supplierProductCode,
                    'xProd' => $item->description,
                    'uCom' => $item->unit,
                    'qCom' => $item->commercialQuantity,
                    'vUnCom' => $item->unitPrice,
                    'vProd' => $item->totalPrice,
                    'nLote' => $item->batch,
                    'qLote' => $item->batchQuantity,
                    'dFab' => $item->manufactureDate,
                    'dVal' => $item->expiryDate,
                    'cAgreg' => $item->aggregationCode,
                    'valid' => true,
                ]);
            }

            $receptionHeader = ReceptionHeader::query()->create([
                'supplier_id' => $supplier->id,
                'xml_nf_header_id' => $xmlHeader->id,
                'status' => 'to receive',
                'rows' => count($document->items),
            ]);

            foreach ($document->items as $item) {
                ReceptionBody::query()->create([
                    'header' => $receptionHeader->id,
                    'product_id' => $productsByCode[$item->supplierProductCode]->id,
                    'xml_nf_body_id' => $xmlItems[$item->nItem]->id,
                    'theoretical' => $item->commercialQuantity,
                    'fabrication' => $item->manufactureDate,
                    'validity' => $item->expiryDate,
                    'batch' => $item->batch,
                ]);
            }

            return new NfeInboundImportResult($xmlHeader, $receptionHeader);
        });
    }

    private function resolveSupplier(string $normalizedCnpj): Supplier
    {
        $suppliers = Supplier::query()
            ->whereRaw("REGEXP_REPLACE(cnpj, '[^0-9]', '') = ?", [$normalizedCnpj])
            ->get();

        if ($suppliers->count() !== 1) {
            $reason = $suppliers->isEmpty() ? 'não encontrado' : 'ambíguo';
            throw new NfeInboundImportException("Fornecedor com CNPJ {$normalizedCnpj} {$reason}.");
        }

        return $suppliers->sole();
    }

    private function resolveDefaultCategory(): Category
    {
        $name = (string) config('nfe.default_category');
        $categories = Category::query()->where('category', $name)->limit(2)->get();

        if ($categories->count() !== 1) {
            $reason = $categories->isEmpty() ? 'não encontrada' : 'ambígua';
            throw new NfeInboundImportException("Categoria default '{$name}' {$reason}.");
        }

        return $categories->sole();
    }

    /** @return array<string, Product> */
    private function resolveProducts(
        NfeInboundDocument $document,
        Supplier $supplier,
        Category $category,
    ): array {
        $products = [];
        foreach ($document->items as $item) {
            if (isset($products[$item->supplierProductCode])) {
                continue;
            }

            $mappings = SupplierProduct::query()
                ->where('supplier_id', $supplier->id)
                ->where('supplier_product_code', $item->supplierProductCode)
                ->limit(2)
                ->get();

            if ($mappings->count() > 1) {
                throw new NfeInboundImportException(
                    "Mapeamento fornecedor/produto ambíguo para código {$item->supplierProductCode}."
                );
            }

            if ($mappings->count() === 1) {
                $products[$item->supplierProductCode] = Product::query()
                    ->findOrFail($mappings->sole()->product_id);
                continue;
            }

            $product = Product::query()->create([
                'id' => $this->newInternalProductId(),
                'description' => $item->description,
                'purchase_price' => $item->unitPrice,
                'category_id' => $category->id,
                'unit' => $item->unit,
                'supplier_default_id' => $supplier->id,
                'enabled' => true,
            ]);

            SupplierProduct::query()->create([
                'supplier_id' => $supplier->id,
                'supplier_product_code' => $item->supplierProductCode,
                'product_id' => $product->id,
            ]);

            $products[$item->supplierProductCode] = $product;
        }

        return $products;
    }

    private function newInternalProductId(): string
    {
        do {
            $id = Str::upper(Str::random(20));
        } while (Product::query()->whereKey($id)->exists());

        return $id;
    }
}
