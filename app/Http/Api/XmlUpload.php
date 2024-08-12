<?php

namespace App\Http\Api;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class XmlUpload 
{
    public static function uploadInbound(Request $request)
    {
        $file = $request->file('file');
        $filename = 'xml/nfs/inbound/' . $request->input('name');
        
        if ($file) {
            Storage::put($filename, file_get_contents($file));
        }
    }

    public function uploadOutbound(Request $request)
    {
        $file = $request->file('file');
        $filename = 'xml/nfs/outbound/' . $request->input('name');
        
        if ($file) {
            Storage::put($filename, file_get_contents($file));
        }
    }
}