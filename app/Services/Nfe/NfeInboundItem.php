<?php

namespace App\Services\Nfe;

final readonly class NfeInboundItem
{
    public function __construct(
        public string $nItem,
        public string $supplierProductCode,
        public string $description,
        public string $unit,
        public string $commercialQuantity,
        public string $unitPrice,
        public string $totalPrice,
        public ?string $batch,
        public ?string $batchQuantity,
        public ?string $manufactureDate,
        public ?string $expiryDate,
        public ?string $aggregationCode,
    ) {
    }
}
