<?php

namespace App\Services\Nfe;

use Illuminate\Support\Facades\Storage;
use Throwable;

final class NfeInboundFileArchiver
{
    private const INBOUND = 'xml/nfs/inbound/';
    private const PROCESSED = 'xml/nfs/processado/inbound/';

    public function archive(string $source): string
    {
        if (dirname($source) !== rtrim(self::INBOUND, '/')) {
            throw new NfeInboundArchiveException('Origem não pertence ao diretório inbound.');
        }

        $filename = basename($source);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            throw new NfeInboundArchiveException('Nome de arquivo inbound inválido.');
        }

        $destination = self::PROCESSED.$filename;
        $disk = Storage::disk('local');
        if (!$disk->exists($source)) {
            throw new NfeInboundArchiveException('Arquivo inbound de origem não existe.');
        }
        if ($disk->exists($destination)) {
            throw new NfeInboundArchiveException('Conflito: destino processado já existe.');
        }

        $sourceStream = null;
        $destinationStream = false;
        $createdDestination = false;
        $sourceDeleted = false;
        $destinationPath = $disk->path($destination);
        $destinationDirectory = dirname($destinationPath);

        try {
            if (!is_dir($destinationDirectory)
                && !@mkdir($destinationDirectory, 0775, true)
                && !is_dir($destinationDirectory)) {
                throw new NfeInboundArchiveException('Não foi possível criar diretório processed.');
            }

            // Exclusive creation closes the check-then-overwrite race for an existing destination.
            $destinationStream = @fopen($destinationPath, 'x');
            if ($destinationStream === false) {
                throw new NfeInboundArchiveException('Conflito: destino processado já existe ou não pode ser criado.');
            }
            $createdDestination = true;

            $sourceStream = $disk->readStream($source);
            if (!is_resource($sourceStream)) {
                throw new NfeInboundArchiveException('Não foi possível ler o arquivo inbound de origem.');
            }

            $copied = stream_copy_to_stream($sourceStream, $destinationStream);
            if ($copied === false || !fflush($destinationStream)) {
                throw new NfeInboundArchiveException('Não foi possível copiar completamente para processed.');
            }

            fclose($destinationStream);
            $destinationStream = false;
            fclose($sourceStream);
            $sourceStream = null;

            if (!$disk->exists($destination)) {
                throw new NfeInboundArchiveException('Filesystem não confirmou o destino processado.');
            }

            if (!$disk->delete($source)) {
                throw new NfeInboundArchiveException('Destino criado, mas não foi possível remover a origem inbound.');
            }
            $sourceDeleted = true;
        } catch (Throwable $exception) {
            if (is_resource($sourceStream)) {
                fclose($sourceStream);
            }
            if (is_resource($destinationStream)) {
                fclose($destinationStream);
            }
            // Only remove a destination this call exclusively created, never a pre-existing file.
            if ($createdDestination && !$sourceDeleted && is_file($destinationPath)) {
                @unlink($destinationPath);
            }
            if ($exception instanceof NfeInboundArchiveException) {
                throw $exception;
            }

            throw new NfeInboundArchiveException('Falha ao mover arquivo para processado.', 0, $exception);
        }

        if ($disk->exists($source) || !$disk->exists($destination)) {
            throw new NfeInboundArchiveException('Filesystem não confirmou o archive do arquivo inbound.');
        }

        return $destination;
    }
}
