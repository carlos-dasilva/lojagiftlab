<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_bundle')->default(false)->after('made_to_order');
        });

        Schema::create('bundle_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            $table->unique(['bundle_id', 'component_product_id']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_channel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('shipping_income', 12, 2)->default(0);
            $table->decimal('fee', 12, 2)->default(0);
            $table->date('sold_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('sold_at');
        });

        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('category')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['paid_at', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payables');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('bundle_product');
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('is_bundle'));
    }
};
