<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class XmlRuleManager
{
    protected $cacheKey = 'xml_validation_rules';

    public function getRules()
    {
        return Cache::rememberForever($this->cacheKey, function () {
            $rules = Storage::get('xml/nfs/rules.json');
            return json_decode($rules, true);
        });
    }

    public function clearCache()
    {
        Cache::forget($this->cacheKey);
    }
}
