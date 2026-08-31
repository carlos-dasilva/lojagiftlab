<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_goals', function (Blueprint $table) {
            $table->id();
            $table->string('period_type', 20);
            $table->decimal('target_amount', 12, 2);
            $table->date('effective_from');
            $table->timestamps();
            $table->unique(['period_type', 'effective_from']);
            $table->index(['period_type', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_goals');
    }
};
