<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('bots', function (Blueprint $t) {
      if (!Schema::hasColumn('bots','channel')) {
        $t->string('channel', 20)->default('web')->index();
      }
      if (!Schema::hasColumn('bots','is_default')) {
        $t->boolean('is_default')->default(false)->index();
      }
      if (!Schema::hasColumn('bots','config')) {
        $t->json('config')->nullable();
      }
      if (!Schema::hasColumn('bots','organization_id')) {
        $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
      }
    });
  }
  public function down(): void {
    Schema::table('bots', function (Blueprint $t) {
      if (Schema::hasColumn('bots','is_default')) $t->dropColumn('is_default');
      if (Schema::hasColumn('bots','channel'))    $t->dropColumn('channel');
      // no tocamos organization_id ni config en down para no perder datos
    });
  }
};
