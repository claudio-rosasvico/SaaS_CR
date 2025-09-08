<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $t) {
            if (!Schema::hasColumn('sources', 'chunks_count')) {
                $t->unsignedInteger('chunks_count')->default(0)->after('status');
            }
        });
    }
    public function down(): void
    {
        Schema::table('sources', function (Blueprint $t) {
            if (Schema::hasColumn('sources', 'chunks_count')) {
                $t->dropColumn('chunks_count');
            }
        });
    }
};
