<?php

return [
    // Resolve categories.category with sole(): missing or ambiguous names must fail.
    // The existing migration creates "default"; no numeric category ID is assumed.
    'default_category' => env('NFE_DEFAULT_CATEGORY', 'default'),
];
