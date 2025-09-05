// database/migrations/2025_09_04_000001_create_organization_user_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('organization_user', function (Blueprint $t) {
      $t->id();
      $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
      $t->foreignId('user_id')->constrained()->cascadeOnDelete();
      $t->string('role', 20)->default('member'); // owner|admin|member
      $t->timestamps();
      $t->unique(['organization_id','user_id']);
      $t->index(['user_id','role']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('organization_user');
  }
};
