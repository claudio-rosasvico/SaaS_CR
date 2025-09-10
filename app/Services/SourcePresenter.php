<?php

namespace App\Services;

class SourcePresenter
{
    public function present(array $hit, array $policy = []): ?string
    {
        $title = $hit['metadata']['title'] ?? ($hit['metadata']['file'] ?? '');
        $title = trim((string)$title);
        if ($title === '') return null;

        $pres = array_replace_recursive([
            'hide_urls'       => true,
            'hide_file_names' => true,
            'aliases'         => ['domains' => [], 'files' => []],
        ], $policy);

        // URL → nombre
        if (preg_match('~^https?://~i', $title)) {
            if (!empty($pres['hide_urls'])) {
                $host = parse_url($title, PHP_URL_HOST) ?: '';
                $host = strtolower($host);

                // alias directo por dominio
                if (isset($pres['aliases']['domains'][$host])) {
                    return $pres['aliases']['domains'][$host];
                }

                // heurística genérica
                $base = preg_replace('/^(www\.|portal\.)/i', '', $host);
                $base = explode('.', $base)[0] ?? '';
                $base = preg_replace('/(entrerios|tur|gob|municipio|ciudad)$/i', '', $base);
                $base = str_replace(['-', '_'], ' ', $base);
                $base = trim($base);
                if ($base === '') return null;

                return mb_convert_case($base, MB_CASE_TITLE, 'UTF-8');
            }
            // si no ocultás URLs, devolvé el host “bonito”
            return parse_url($title, PHP_URL_HOST) ?? $title;
        }

        // Archivo → limpio nombre
        $name = $title;
        if ($pres['hide_file_names']) {
            $name = preg_replace('/\.(pdf|docx?|xlsx?|pptx?)$/i', '', $name);
            $name = preg_replace('/^(final|borrador|version|v\d+|mr)\W*/i', '', $name);
            $name = preg_replace('/[_-]+/', ' ', $name);
            $name = trim($name);
        }

        // alias por patrón simple (archivo/título)
        foreach ($pres['aliases']['files'] as $pattern => $replacement) {
            if (@preg_match('/' . $pattern . '/i', $name)) {
                return $replacement;
            }
        }

        // filtrito genérico
        if (preg_match('/localidades/i', $name)) {
            return 'varias localidades';
        }

        return $name !== '' ? mb_convert_case($name, MB_CASE_TITLE, 'UTF-8') : null;
    }
}
