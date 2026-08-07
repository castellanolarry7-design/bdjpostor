<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->string('user_id');
            $table->string('sale_number', 50);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->json('payments');
            $table->decimal('total_received_usd', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->string('base_currency', 10)->default('USD');
            $table->text('notes')->nullable();
            $table->enum('status', ['completed', 'voided'])->default('completed');
            $table->timestamp('sold_at');
            $table->timestamps();
            $table->index('tenant_id');
            $table->index(['tenant_id', 'sold_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sale_id');
            $table->string('product_id');
            $table->string('product_name', 200);
            $table->string('product_sku', 100)->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
            $table->index('sale_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
