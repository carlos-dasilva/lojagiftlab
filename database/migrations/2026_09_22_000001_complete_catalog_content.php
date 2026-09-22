<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->longText('body');
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable();
            $table->string('catalog_path')->nullable();
        });
        Schema::table('product_sales_links', function (Blueprint $table) {
            $table->decimal('original_price', 12, 2)->nullable();
        });
        Schema::create('product_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_redirects');
        Schema::table('product_sales_links', fn (Blueprint $table) => $table->dropColumn('original_price'));
        Schema::table('product_images', fn (Blueprint $table) => $table->dropColumn(['thumbnail_path', 'catalog_path']));
        Schema::dropIfExists('content_pages');
    }
};
