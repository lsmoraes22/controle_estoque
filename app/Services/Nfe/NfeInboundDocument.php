<?php

namespace App\Services\Nfe;

final readonly class NfeInboundDocument
{
    /** @param list<NfeInboundItem> $items */
    public function __construct(
        public string $id,
        public ?string $version,
        public string $supplierCnpj,
        public array $items,
    ) {
    }
}
