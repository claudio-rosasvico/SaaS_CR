<?php

namespace App\Services;

use App\Models\KnowledgeChunk;
use Illuminate\Support\Facades\DB;

class RetrievalService
{
    /** @var EmbeddingService|null */
    private $emb;

    public function __construct(?EmbeddingService $emb = null)
    {
        $this->emb = $emb;
    }

    public function search(string $q, int $k = 6, string $mode = null): array
    {
        $mode = $mode ?: (string) env('RETRIEVAL_MODE', 'semantic');
        $orgId = current_org_id() ?: 0;

        // cache key POR ORG
        $cacheKey = "rag:search:{$orgId}:" . md5("{$mode}|{$k}|{$q}");
        return cache()->remember($cacheKey, 30, function () use ($q, $k, $mode, $orgId) {

            if ($mode === 'keyword') {
                // FULLTEXT o LIKE, PERO con filtro de org
                return KnowledgeChunk::query()
                    ->select(['id', 'content', 'metadata'])
                    ->whereRaw("MATCH(content) AGAINST (? IN NATURAL LANGUAGE MODE)", [$q])
                    ->limit($k)
                    ->get()
                    ->map(fn($c) => [
                        'id' => $c->id,
                        'content' => $c->content,
                        'metadata' => (array) $c->metadata,
                        'score' => null,
                    ])->all();
            }

            // === SEMÁNTICO ===
            // 1) Obtener embedding de la query
            $queryVec = app(EmbeddingService::class)->embed($q); // array<float>
            if (!$queryVec) return [];

            // 2) Buscar candidatos SOLO de esta org (p.ej top-N por fecha o random) y calcular similitud en PHP
            //    Si tenés muchas filas, hacé un pre-filtro (por ejemplo últimos X días o limit 2000)
            $candidates = KnowledgeChunk::query()
                ->whereNotNull('embedding')
                ->limit(2000) // ajustá si hace falta
                ->get(['id', 'content', 'metadata', 'embedding']);

            // 3) Rankear por coseno
            $scored = [];
            foreach ($candidates as $c) {
                $emb = is_string($c->embedding) ? json_decode($c->embedding, true) : $c->embedding;
                if (!is_array($emb)) continue;

                $score = $this->cosine($queryVec, $emb);
                $scored[] = [
                    'id'      => $c->id,
                    'content' => $c->content,
                    'metadata' => (array) $c->metadata,
                    'score'   => $score,
                ];
            }

            // 4) Orden y umbral
            usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
            $scored = array_filter($scored, fn($x) => $x['score'] >= 0.15); // umbral suave
            return array_slice(array_values($scored), 0, $k);
        });
    }

    protected function fulltextSearch(string $query, int $limit): array
    {
        $q = trim($query);
        if ($q === '') return [];

        try {
            $rows = DB::select("
                SELECT kc.id, kc.source_id, kc.content, kc.metadata,
                       MATCH(kc.content) AGAINST (? IN NATURAL LANGUAGE MODE) AS score
                FROM knowledge_chunks kc
                WHERE MATCH(kc.content) AGAINST (? IN NATURAL LANGUAGE MODE)
                ORDER BY score DESC
                LIMIT ?
            ", [$q, $q, $limit]);

            return array_map(fn($r) => [
                'id' => $r->id,
                'source_id' => $r->source_id,
                'content' => $r->content,
                'metadata' => is_string($r->metadata) ? json_decode($r->metadata, true) : $r->metadata,
                'score' => $r->score,
            ], $rows);
        } catch (\Throwable $e) {
            // fallback LIKE
            $rows = DB::table('knowledge_chunks')
                ->select('id', 'source_id', 'content', 'metadata')
                ->where('content', 'like', '%' . $q . '%')
                ->limit($limit)->get();

            return $rows->map(fn($r) => [
                'id' => $r->id,
                'source_id' => $r->source_id,
                'content' => $r->content,
                'metadata' => is_string($r->metadata) ? json_decode($r->metadata, true) : $r->metadata,
                'score' => null,
            ])->all();
        }
    }

    protected function semanticSearch(string $query, int $limit): array
    {
        $q = trim($query);
        if ($q === '') return [];

        // 1) Vector de la consulta
        $qv = $this->emb->embed($q);

        // 2) Pre-filtrado con FULLTEXT (rápido) para no comparar todo
        $cands = [];
        try {
            $cands = DB::select("
                SELECT id, source_id, content, metadata,
                       MATCH(content) AGAINST (? IN NATURAL LANGUAGE MODE) AS ft
                FROM knowledge_chunks
                WHERE MATCH(content) AGAINST (? IN NATURAL LANGUAGE MODE)
                ORDER BY ft DESC
                LIMIT 200
            ", [$q, $q]);
        } catch (\Throwable $e) {
            // si FULLTEXT aún no está, tomamos los últimos 200 como candidato
            $cands = DB::table('knowledge_chunks')
                ->select('id', 'source_id', 'content', 'metadata')
                ->latest()->limit(200)->get()->all();
        }

        // 3) Cargar embeddings y calcular coseno; combinar con fulltext (si existe)
        $scored = [];
        foreach ($cands as $r) {
            $meta = is_string($r->metadata) ? json_decode($r->metadata, true) : ($r->metadata ?? []);
            $row = DB::table('knowledge_chunks')->where('id', $r->id)->first(['embedding']);
            $emb = (isset($row) && isset($row->embedding)) ? (is_string($row->embedding) ? json_decode($row->embedding, true) : $row->embedding) : null;
            if (!$emb || !is_array($emb)) continue;

            $cos = $this->cosine($qv, $emb);
            $ft  = isset($r->ft) ? (float)$r->ft : 0.0;

            // mezcla simple: 70% vector, 30% fulltext normalizado (0..1)
            $score = 0.7 * $cos + 0.3 * $this->sigmoid($ft);

            $scored[] = [
                'id' => $r->id,
                'source_id' => $r->source_id,
                'content' => $r->content,
                'metadata' => $meta,
                'score' => $score,
            ];
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scored, 0, $limit);
    }

    private function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        $n = min(count($a), count($b));
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $na  += $a[$i] * $a[$i];
            $nb  += $b[$i] * $b[$i];
        }
        if ($na <= 0 || $nb <= 0) return 0.0;
        return $dot / (sqrt($na) * sqrt($nb));
    }

    protected function sigmoid(float $x): float
    {
        // suaviza ft_score a 0..1
        return 1 / (1 + exp(-$x));
    }

    public function buildContext(array $hits, int $limit = 1800): string
    {
        $out = '';
        foreach ($hits as $h) {
            $chunk = trim((string) $h['content']);
            if ($chunk === '') continue;
            if (mb_strlen($out) + mb_strlen($chunk) + 2 > $limit) break;
            $out .= ($out === '' ? '' : "\n\n") . $chunk;
        }
        return $out;
    }
}
