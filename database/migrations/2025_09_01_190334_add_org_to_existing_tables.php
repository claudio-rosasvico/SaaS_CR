<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    foreach (['sources','knowledge_chunks','conversations','messages'] as $table) {
      Schema::table($table, function (Blueprint $t) {
        $t->foreignId('organization_id')->nullable()->after('id');
      });
    }
  }
  public function down(): void {
    foreach (['sources','knowledge_chunks','conversations','messages'] as $table) {
      Schema::table($table, function (Blueprint $t) {
        $t->dropColumn('organization_id');
      });
    }
  }
};

