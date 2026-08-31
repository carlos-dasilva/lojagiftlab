<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_channel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('customer_name');
            $table->string('customer_contact')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('shipping_income', 12, 2)->default(0);
            $table->decimal('fee', 12, 2)->default(0);
            $table->date('sold_at');
            $table->date('due_date')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['received_at', 'due_date']);
            $table->index(['sold_at', 'delivered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_sales');
    }
};
