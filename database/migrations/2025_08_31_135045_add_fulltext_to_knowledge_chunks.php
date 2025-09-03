<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL 8: FULLTEXT sobre InnoDB
        DB::statement('ALTER TABLE knowledge_chunks ADD FULLTEXT idx_content (content)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE knowledge_chunks DROP INDEX idx_content');
    }
};
