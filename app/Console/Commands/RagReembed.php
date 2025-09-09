<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RagReembed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:reembed {sourceId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $q = \App\Models\KnowledgeChunk::query()->whereNull('embedding');
        if ($id = $this->argument('sourceId')) $q->where('source_id', (int)$id);
        $ids = $q->pluck('id');
        foreach ($ids as $id) \App\Jobs\EmbedChunkJob::dispatch($id)->onQueue('embeddings');
        $this->info("Re-despachados: " . count($ids));
    }
}
