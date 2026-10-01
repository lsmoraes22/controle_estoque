<?php

namespace App\Services\Nfe;

use App\Models\ReceptionBody;
use App\Models\ReceptionHeader;
use App\Models\XmlNfBody;
use App\Models\XmlNfHeader;

final class NfeInboundImportCompleteness
{
    public function isComplete(NfeInboundDocument $document): bool
    {
        $headers = XmlNfHeader::query()->where('idnf', $document->id)->limit(2)->get();
        if ($headers->count() !== 1) {
            return false;
        }
        $xmlHeader = $headers->sole();

        $receptionHeaders = ReceptionHeader::query()
            ->where('xml_nf_header_id', $xmlHeader->id)
            ->limit(2)
            ->get();
        if ($receptionHeaders->count() !== 1) {
            return false;
        }
        $receptionHeader = $receptionHeaders->sole();

        $xmlItems = XmlNfBody::query()->where('header', $xmlHeader->id)->get(['id', 'nItem']);
        if ($xmlItems->count() !== count($document->items)) {
            return false;
        }

        $expectedNumbers = array_map(static fn (NfeInboundItem $item): string => $item->nItem, $document->items);
        $persistedNumbers = $xmlItems->pluck('nItem')->map(static fn ($number): string => (string) $number)->all();
        sort($expectedNumbers, SORT_STRING);
        sort($persistedNumbers, SORT_STRING);
        if ($expectedNumbers !== $persistedNumbers) {
            return false;
        }

        if (ReceptionBody::query()->where('header', $receptionHeader->id)->count() !== count($document->items)) {
            return false;
        }

        foreach ($xmlItems as $xmlItem) {
            $receptionBodies = ReceptionBody::query()
                ->where('xml_nf_body_id', $xmlItem->id)
                ->limit(2)
                ->get(['header']);
            if ($receptionBodies->count() !== 1
                || (int) $receptionBodies->sole()->header !== (int) $receptionHeader->id) {
                return false;
            }
        }

        return true;
    }
}
