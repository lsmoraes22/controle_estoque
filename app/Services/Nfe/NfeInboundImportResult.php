<?php

namespace App\Services\Nfe;

use App\Models\ReceptionHeader;
use App\Models\XmlNfHeader;

final readonly class NfeInboundImportResult
{
    public function __construct(
        public XmlNfHeader $xmlHeader,
        public ReceptionHeader $receptionHeader,
    ) {
    }
}
