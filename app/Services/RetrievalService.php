<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RetrievalService
{
    /** @var EmbeddingService|null */
    private $emb;

    public function __construct(?EmbeddingService $emb = null)
    {
        $this->emb = $emb;
    }

    public function search(string $query, int $limit = 5): array
    {
        $mode = env('RETRIEVAL_MODE', 'fulltext');

        if ($mode === 'semantic' && $this->emb) {
            return $this->semanticSearch($query, $limit);
        }

        return $this->fulltextSearch($query, $limit);
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

            return array_map(fn($r)=>[
                'id'=>$r->id,'source_id'=>$r->source_id,'content'=>$r->content,
                'metadata'=>is_string($r->metadata)?json_decode($r->metadata,true):$r->metadata,
                'score'=>$r->score,
            ], $rows);

        } catch (\Throwable $e) {
            // fallback LIKE
            $rows = DB::table('knowledge_chunks')
                ->select('id','source_id','content','metadata')
                ->where('content','like','%'.$q.'%')
                ->limit($limit)->get();

            return $rows->map(fn($r)=>[
                'id'=>$r->id,'source_id'=>$r->source_id,'content'=>$r->content,
                'metadata'=>is_string($r->metadata)?json_decode($r->metadata,true):$r->metadata,
                'score'=>null,
            ])->all();
        }
    }

    protected function semanticSearch(string $query, int $limit): array
    {
        $q = trim($query);
        if ($q === '') return [];

        // 1) Vector de la consulta
        $qv = $this->emb->embedText($q);

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
                ->select('id','source_id','content','metadata')
                ->latest()->limit(200)->get()->all();
        }

        // 3) Cargar embeddings y calcular coseno; combinar con fulltext (si existe)
        $scored = [];
        foreach ($cands as $r) {
            $meta = is_string($r->metadata) ? json_decode($r->metadata, true) : ($r->metadata ?? []);
            $row = DB::table('knowledge_chunks')->where('id',$r->id)->first(['embedding']);
            $emb = (isset($row) && isset($row->embedding)) ? (is_string($row->embedding) ? json_decode($row->embedding,true) : $row->embedding) : null;
            if (!$emb || !is_array($emb)) continue;

            $cos = $this->cosine($qv, $emb);
            $ft  = isset($r->ft) ? (float)$r->ft : 0.0;

            // mezcla simple: 70% vector, 30% fulltext normalizado (0..1)
            $score = 0.7*$cos + 0.3*$this->sigmoid($ft);

            $scored[] = [
                'id'=>$r->id,'source_id'=>$r->source_id,'content'=>$r->content,
                'metadata'=>$meta,'score'=>$score,
            ];
        }

        usort($scored, fn($a,$b)=> $b['score'] <=> $a['score']);
        return array_slice($scored, 0, $limit);
    }

    protected function cosine(array $a, array $b): float
    {
        $dot=0; $na=0; $nb=0; $n=min(count($a), count($b));
        for ($i=0; $i<$n; $i++) { $dot += $a[$i]*$b[$i]; $na += $a[$i]**2; $nb += $b[$i]**2; }
        if ($na==0 || $nb==0) return 0.0;
        return $dot / (sqrt($na)*sqrt($nb));
    }

    protected function sigmoid(float $x): float
    {
        // suaviza ft_score a 0..1
        return 1 / (1 + exp(-$x));
    }

    public function buildContext(array $chunks, int $maxChars = 1800): string
    {
        $out=[]; $len=0;
        foreach ($chunks as $c) {
            $title = $c['metadata']['title'] ?? 'Fuente';
            $text  = trim(preg_replace('/\s+/', ' ', $c['content']));
            $frag  = "— {$title}: {$text}";
            if ($len + strlen($frag) > $maxChars) break;
            $out[] = $frag; $len += strlen($frag);
        }
        return implode("\n", $out);
    }
}
