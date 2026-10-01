<?php

namespace App\Http\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class XmlUpload 
{
    public static function uploadInbound(Request $request)
    {
        $file = $request->file('file');

        if ($file === null || !$file->isValid()) {
            return response()->json(['error' => 'Envie um arquivo XML válido.'], 422);
        }

        $maxBytes = (int) config('nfe.max_xml_bytes', 2 * 1024 * 1024);
        $size = $file->getSize();
        if ($maxBytes < 1 || $size === false || $size > $maxBytes) {
            return response()->json(['error' => 'O arquivo excede o tamanho máximo permitido.'], 413);
        }

        $disk = Storage::disk('local');
        $destinationPath = null;
        $destinationStream = false;
        $sourceStream = false;
        $createdDestination = false;
        try {
            do {
                $filename = Str::uuid().'.xml';
                $path = 'xml/nfs/inbound/'.$filename;
                $destination = 'xml/nfs/processado/inbound/'.$filename;
            } while ($disk->exists($path) || $disk->exists($destination));

            $directory = dirname($disk->path($path));
            if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException('Unable to create inbound upload directory.');
            }

            $destinationPath = $disk->path($path);
            $destinationStream = @fopen($destinationPath, 'x');
            if ($destinationStream === false) {
                throw new \RuntimeException('Inbound upload destination already exists.');
            }
            $createdDestination = true;

            $sourceStream = fopen($file->getRealPath(), 'rb');
            if ($sourceStream === false || stream_copy_to_stream($sourceStream, $destinationStream) === false
                || !fflush($destinationStream)) {
                throw new \RuntimeException('Inbound upload stream could not be copied.');
            }

            fclose($sourceStream);
            $sourceStream = false;
            fclose($destinationStream);
            $destinationStream = false;
            if (!$disk->exists($path)) {
                throw new \RuntimeException('Filesystem did not confirm inbound upload.');
            }

            Log::info('Inbound NF-e upload stored.', ['file' => $filename]);

            return response()->json(['status' => 'stored', 'file' => $filename], 201);
        } catch (Throwable $exception) {
            if (is_resource($sourceStream)) {
                fclose($sourceStream);
            }
            if (is_resource($destinationStream)) {
                fclose($destinationStream);
            }
            if ($createdDestination && $destinationPath !== null && is_file($destinationPath)) {
                @unlink($destinationPath);
            }
            Log::error('Inbound NF-e upload failed.', ['message' => $exception->getMessage()]);

            return response()->json(['error' => 'Não foi possível armazenar o arquivo XML.'], 500);
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