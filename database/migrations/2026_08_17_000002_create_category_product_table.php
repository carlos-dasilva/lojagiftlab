<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'product_id']);
        });

        DB::table('products')
            ->whereNotNull('category_id')
            ->orderBy('id')
            ->get()
            ->each(function ($product): void {
                DB::table('category_product')->insertOrIgnore([
                    'category_id' => $product->category_id,
                    'product_id' => $product->id,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
