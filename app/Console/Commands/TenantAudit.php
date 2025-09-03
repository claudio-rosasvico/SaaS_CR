<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantAudit extends Command
{
    protected $signature = 'tenant:audit {--fix} {--org=1}';
    protected $description = 'Audita organization_id en tablas clave y opcionalmente corrige nulos';

    public function handle()
    {
        $tables = ['sources','knowledge_chunks','conversations','messages'];
        $orgId  = (int)$this->option('org');

        foreach ($tables as $t) {
            $missing = DB::table($t)->whereNull('organization_id')->count();
            $this->line(sprintf('%-20s : %d sin organization_id', $t, $missing));

            if ($missing > 0 && $this->option('fix')) {
                DB::table($t)->whereNull('organization_id')->update(['organization_id'=>$orgId]);
                $after = DB::table($t)->whereNull('organization_id')->count();
                $this->info("  -> corregidos, quedan {$after}");
            }
        }

        return 0;
    }
}
