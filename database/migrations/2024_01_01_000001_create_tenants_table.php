<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->string('slug', 100)->unique();
            $table->string('email', 150)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('plan', 30)->default('free');
            $table->boolean('active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('active');
        });
    }
    public function down(): void { Schema::dropIfExists('tenants'); }
};
