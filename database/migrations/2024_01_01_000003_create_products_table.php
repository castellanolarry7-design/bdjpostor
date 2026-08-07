<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->string('name', 200);
            $table->string('sku', 100);
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('stock_current')->default(0);
            $table->unsignedInteger('stock_minimum')->default(5);
            $table->string('unit', 30)->default('unidad');
            $table->decimal('cost', 10, 2)->default(0.00);
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('supplier', 200)->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'sku'], 'unique_sku_per_tenant');
            $table->index('tenant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
