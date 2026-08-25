<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->uuid('installment_group')->nullable()->after('id')->index();
            $table->unsignedSmallInteger('installment_number')->default(1)->after('amount');
            $table->unsignedSmallInteger('installments_total')->default(1)->after('installment_number');
        });
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->dropIndex(['installment_group']);
            $table->dropColumn(['installment_group', 'installment_number', 'installments_total']);
        });
    }
};
