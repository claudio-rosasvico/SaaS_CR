<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RagReingest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:reingest {sourceId}';

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
        \App\Jobs\IngestSourceJob::dispatch((int)$this->argument('sourceId'))->onQueue('ingest');
        $this->info('Re-ingesta encolada.');
    }
}
