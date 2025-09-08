<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $t) {
            if (!Schema::hasColumn('sources', 'chunks_count'))    $t->unsignedInteger('chunks_count')->default(0)->after('status');
            if (!Schema::hasColumn('sources', 'embedded_count'))  $t->unsignedInteger('embedded_count')->default(0)->after('chunks_count');
            if (!Schema::hasColumn('sources', 'error'))           $t->text('error')->nullable()->after('embedded_count');
        });
    }
    public function down(): void
    {
        Schema::table('sources', function (Blueprint $t) {
            foreach (['error', 'embedded_count', 'chunks_count'] as $c) {
                if (Schema::hasColumn('sources', $c)) $t->dropColumn($c);
            }
        });
    }
};
