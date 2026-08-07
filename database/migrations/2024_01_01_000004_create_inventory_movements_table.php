<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->string('product_id');
            $table->string('user_id')->nullable();
            $table->enum('type', ['entrada', 'salida', 'ajuste']);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('stock_before')->default(0);
            $table->unsignedInteger('stock_after')->default(0);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->string('reference', 100)->nullable();
            $table->timestamp('moved_at');
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('product_id');
            $table->index(['tenant_id', 'product_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_movements'); }
};
