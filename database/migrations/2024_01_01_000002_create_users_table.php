<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->string('email', 150)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['super_admin', 'admin', 'user', 'cashier'])->default('user');
            $table->boolean('active')->default(true);
            $table->string('avatar_url')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('tenant_id')->nullable()->constrained()->cascadeOnDelete();            $table->index('role');
            $table->index('active');
        });
    }
    public function down(): void { Schema::dropIfExists('users'); }
};
