<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        DB::table('credit_sales')->orderBy('id')->each(function ($credit) {
            DB::table('credit_sale_items')->insert([
                'credit_sale_id' => $credit->id,
                'product_id' => $credit->product_id,
                'item_name' => $credit->product_name,
                'quantity' => $credit->quantity,
                'unit_price' => $credit->unit_price,
                'order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_sale_items');
    }
};
