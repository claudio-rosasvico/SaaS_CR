<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('knowledge_chunks', function (Blueprint $t) {
      if (!Schema::hasColumn('knowledge_chunks','embedding')) {
        $t->json('embedding')->nullable()->after('content');
      }
      if (!Schema::hasColumn('knowledge_chunks','embedded_at')) {
        $t->timestamp('embedded_at')->nullable()->after('embedding');
      }
    });
  }
  public function down(): void {
    Schema::table('knowledge_chunks', function (Blueprint $t) {
      if (Schema::hasColumn('knowledge_chunks','embedded_at')) $t->dropColumn('embedded_at');
      if (Schema::hasColumn('knowledge_chunks','embedding'))   $t->dropColumn('embedding');
    });
  }
};
