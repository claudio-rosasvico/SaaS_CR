<?php

namespace App\Services;

class SourcePresenter
{
    /**
     * $hit: ['content'=>..., 'metadata'=> ['title'=>?, 'file'=>?, 'url'=>?, 'domain'=>?]]
     * $cfg:
     *   - citations (bool)
     *   - presentation => [
     *       hide_urls (bool), hide_file_names (bool),
     *       max_suggestions (int),
     *       aliases => [
     *         'domains' => ['dominio' => 'Alias legible'],
     *         'files'   => ['~/regex/i' => 'Alias legible']
     *       ]
     *     ]
     */
    public function citations(array $hits, array $cfg): array
    {
        if (!($cfg['citations'] ?? false)) {
            return [];
        }

        $pres = (array)($cfg['presentation'] ?? []);
        $limit = (int)($pres['max_suggestions'] ?? 3);

        $labels = [];
        foreach ($hits as $h) {
            $label = $this->present($h, $pres);
            if ($label && $label !== 'una fuente verificada') {
                $labels[] = $label;
            }
            if (count($labels) >= $limit) break;
        }
        $labels = array_values(array_unique($labels));

        return array_map(fn($t) => ['title' => $t], $labels);
    }

    public function fallback(array $hits, array $cfg): string
    {
        $pres  = (array)($cfg['presentation'] ?? []);
        $limit = (int)($pres['max_suggestions'] ?? 3);

        $labels = [];
        foreach ($hits as $h) {
            $label = $this->present($h, $pres);
            if ($label && $label !== 'una fuente verificada') {
                $labels[] = $label;
            }
            if (count($labels) >= $limit) break;
        }
        $labels = array_values(array_unique($labels));

        if (count($labels) > 0) {
            $list = implode(' • ', $labels);
            return "Te propongo estas opciones: {$list}. ¿Querés que te arme un mini itinerario?";
        }

        return "Puedo orientarte con ideas según tu estilo (relax, naturaleza, termas). ¿Preferís algo tranquilo y familiar, o más activo con caminatas?";
    }

    /**
     * Retorna un nombre “presentable” para la fuente, evitando URLs crudas y filenames,
     * con soporte de alias por dominio/regex de archivo.
     */
    public function present(array $hit, array $pres): ?string
    {
        $meta   = $hit['metadata'] ?? [];
        $title  = (string) ($meta['title'] ?? '');
        $file   = (string) ($meta['file']  ?? '');
        $url    = (string) ($meta['url']   ?? '');
        $domain = (string) ($meta['domain']?? $this->extractDomain($url));

        $hideUrls      = (bool) ($pres['hide_urls'] ?? true);
        $hideFileNames = (bool) ($pres['hide_file_names'] ?? true);

        $aliases       = (array)($pres['aliases'] ?? []);
        $domainAliases = (array)($aliases['domains'] ?? []);
        $fileAliases   = (array)($aliases['files']   ?? []);

        // 1) Alias por dominio
        if ($domain && isset($domainAliases[$domain])) {
            return trim($domainAliases[$domain]);
        }

        // 2) Alias por filename con regex
        $baseName = $this->basenameLike($title) ?: $this->basenameLike($file);
        if ($baseName) {
            foreach ($fileAliases as $regex => $alias) {
                try {
                    if (@preg_match($regex, $baseName) && preg_match($regex, $baseName)) {
                        return trim($alias);
                    }
                } catch (\Throwable $e) {
                    // regex inválida → ignorar
                }
            }
        }

        // 3) Título humano si no parece URL ni filename
        if ($title && !$this->looksLikeFileName($title) && !$this->looksLikeUrl($title)) {
            return trim($title);
        }

        // 4) Si no ocultamos URL y tenemos dominio, devolvemos algo amable
        if (!$hideUrls && $domain) {
            return "sitio oficial de {$domain}";
        }

        // 5) Si no ocultamos filenames y tenemos uno
        if ($baseName && !$hideFileNames) {
            return $baseName;
        }

        // 6) Ultra neutro
        return "una fuente verificada";
    }

    private function extractDomain(string $url): string
    {
        if (!$url) return '';
        $h = parse_url($url, PHP_URL_HOST);
        return $h ? preg_replace('/^www\./i', '', $h) : '';
    }

    private function looksLikeUrl(string $s): bool
    {
        return (bool) preg_match('~^https?://~i', $s);
    }

    private function looksLikeFileName(string $s): bool
    {
        return (bool) preg_match('/\.(pdf|docx?|pptx?|xlsx?|txt)$/i', $s);
    }

    private function basenameLike(string $s): ?string
    {
        if (!$s) return null;
        if ($this->looksLikeUrl($s)) {
            $path = parse_url($s, PHP_URL_PATH) ?? '';
            $bn = trim(basename($path), '/');
            return $bn ?: null;
        }
        if ($this->looksLikeFileName($s)) {
            return $s;
        }
        return null;
    }
}
