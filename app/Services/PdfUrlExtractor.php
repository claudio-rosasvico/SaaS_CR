<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Str;

class PdfUrlExtractor
{
    /**
     * Extrae texto y metadatos de un PDF.
     * Acepta path local (storage_path, public_path, etc.) o una URL http/https.
     *
     * @return array{text:string, meta:array{title:?string,author:?string,pages:int,file:?string,source:?string}}
     */
    public function extract(string $pathOrUrl): array
    {
        $isUrl = (bool) preg_match('#^https?://#i', $pathOrUrl);
        $localPath = $isUrl ? $this->downloadTemp($pathOrUrl) : $this->normalizePath($pathOrUrl);

        $parser = new Parser();
        $pdf    = $parser->parseFile($localPath);

        $text    = $pdf->getText() ?? '';
        $details = $pdf->getDetails() ?? [];
        $pages   = $pdf->getPages();

        // Limpieza del temp si bajamos desde URL
        if ($isUrl) {
            @unlink($localPath);
        }

        return [
            'text' => trim($text),
            'meta' => [
                'title'  => $details['Title']  ?? null,
                'author' => $details['Author'] ?? null,
                'pages'  => is_array($pages) ? count($pages) : (int) $pages,
                'file'   => $isUrl ? null : basename($localPath),
                'source' => $isUrl ? $pathOrUrl : null,
            ],
        ];
    }

    protected function downloadTemp(string $url): string
    {
        $res = Http::timeout(30)->get($url);
        if (!$res->ok()) {
            throw new \RuntimeException("No se pudo descargar el PDF: {$url}");
        }
        $name = 'tmp/' . Str::uuid() . '.pdf';
        Storage::put($name, $res->body());
        return storage_path('app/' . $name);
    }

    protected function normalizePath(string $path): string
    {
        if (is_file($path)) return $path;

        // 1) intento en storage/app/<path>
        $candidate = storage_path('app/' . $path);
        if (is_file($candidate)) return $candidate;

        // 2) intento en storage/app/public/<path>  👈 PARCHE CLAVE
        $candidate = storage_path('app/public/' . $path);
        if (is_file($candidate)) return $candidate;

        // 3) intento en public_path (por si lo moviste ahí)
        $candidate = public_path($path);
        if (is_file($candidate)) return $candidate;

        throw new \InvalidArgumentException("PDF no encontrado en: {$path}");
    }
}
