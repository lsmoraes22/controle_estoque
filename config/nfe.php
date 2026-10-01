<?php

$maxXmlBytes = (int) env('NFE_MAX_XML_BYTES', env('NFE_INBOUND_MAX_XML_BYTES', 2 * 1024 * 1024));

return [
    'max_xml_bytes' => $maxXmlBytes,
    // Resolve categories.category with sole(): missing or ambiguous names must fail.
    // The existing migration creates "default"; no numeric category ID is assumed.
    'default_category' => env('NFE_DEFAULT_CATEGORY', 'default'),

    'inbound' => [
        'max_xml_bytes' => $maxXmlBytes,
        'max_items' => (int) env('NFE_INBOUND_MAX_ITEMS', 990),
    ],
];
