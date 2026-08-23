<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('sale_price', 12, 2)->nullable()->change();
            $table->decimal('weight_kg', 8, 3)->nullable()->after('stock');
            $table->decimal('width_cm', 8, 2)->nullable()->after('weight_kg');
            $table->decimal('height_cm', 8, 2)->nullable()->after('width_cm');
            $table->decimal('length_cm', 8, 2)->nullable()->after('height_cm');
        });

        Schema::table('product_sales_links', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->after('url');
        });

        DB::table('product_sales_links')->orderBy('id')->eachById(function ($link) {
            $price = DB::table('products')->where('id', $link->product_id)->value('sale_price');
            DB::table('product_sales_links')->where('id', $link->id)->update(['price' => $price]);
        });

        Schema::create('product_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('youtube_id', 20);
            $table->string('title')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'youtube_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_videos');
        Schema::table('product_sales_links', fn (Blueprint $table) => $table->dropColumn('price'));
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['weight_kg', 'width_cm', 'height_cm', 'length_cm']);
            $table->decimal('sale_price', 12, 2)->nullable(false)->change();
        });
    }
};
