<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_admin')->default(false)->index());
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->string('image')->nullable();
            $t->string('icon')->nullable();
            $t->unsignedInteger('order')->default(0);
            $t->boolean('active')->default(true)->index();
            $t->timestamps();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('sku')->nullable()->unique();
            $t->text('short_description')->nullable();
            $t->longText('description')->nullable();
            $t->decimal('cost_price', 12, 2)->default(0);
            $t->decimal('sale_price', 12, 2);
            $t->decimal('discount_percentage', 5, 2)->default(0);
            $t->integer('stock')->nullable();
            $t->string('condition')->default('new');
            $t->text('condition_notes')->nullable();
            $t->boolean('featured')->default(false)->index();
            $t->boolean('is_new')->default(false)->index();
            $t->boolean('customizable')->default(false);
            $t->boolean('made_to_order')->default(false);
            $t->unsignedInteger('order')->default(0);
            $t->string('status')->default('draft')->index();
            $t->timestamps();
            $t->index(['status', 'category_id']);
        });
        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('alt')->nullable();
            $t->boolean('is_primary')->default(false);
            $t->unsignedInteger('order')->default(0);
            $t->timestamps();
        });
        Schema::create('product_attributes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('value');
            $t->string('unit')->nullable();
            $t->unsignedInteger('order')->default(0);
            $t->timestamps();
        });
        Schema::create('product_variants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('sku')->nullable()->unique();
            $t->json('options')->nullable();
            $t->decimal('price_adjustment', 12, 2)->default(0);
            $t->integer('stock')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('tags', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('product_tag', function (Blueprint $t) {
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $t->primary(['product_id', 'tag_id']);
        });
        Schema::create('sales_channels', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('icon')->nullable();
            $t->string('color', 20)->nullable();
            $t->string('base_url')->nullable();
            $t->boolean('active')->default(true)->index();
            $t->unsignedInteger('order')->default(0);
            $t->timestamps();
        });
        Schema::create('product_sales_links', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('sales_channel_id')->constrained()->cascadeOnDelete();
            $t->text('url');
            $t->string('label')->nullable();
            $t->text('message')->nullable();
            $t->unsignedInteger('order')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('group')->index();
            $t->string('key')->unique();
            $t->longText('value')->nullable();
            $t->string('type')->default('text');
            $t->timestamps();
        });
        Schema::create('product_views', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('session_hash', 64);
            $t->timestamp('viewed_at');
            $t->unique(['product_id', 'session_hash']);
        });
        Schema::create('faq_items', function (Blueprint $t) {
            $t->id();
            $t->string('question');
            $t->longText('answer');
            $t->string('category')->nullable();
            $t->unsignedInteger('order')->default(0);
            $t->boolean('active')->default(true)->index();
            $t->timestamps();
        });
        Schema::create('banners', function (Blueprint $t) {
            $t->id();
            $t->string('desktop_image')->nullable();
            $t->string('mobile_image')->nullable();
            $t->string('title');
            $t->text('subtitle')->nullable();
            $t->string('button_label')->nullable();
            $t->string('url')->nullable();
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->boolean('active')->default(true);
            $t->unsignedInteger('order')->default(0);
            $t->timestamps();
        });
        Schema::create('contact_messages', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('subject');
            $t->text('message');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('faq_items');
        Schema::dropIfExists('product_views');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('product_sales_links');
        Schema::dropIfExists('sales_channels');
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin'));
    }
};
