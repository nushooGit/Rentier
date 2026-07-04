<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rent_payments', function (Blueprint $table) {
            $table->string('payment_type')->default('rent')->after('currency');
            $table->unsignedTinyInteger('period_month')->nullable()->change();
            $table->unsignedSmallInteger('period_year')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rent_payments', function (Blueprint $table) {
            $table->dropColumn('payment_type');
            $table->unsignedTinyInteger('period_month')->nullable(false)->change();
            $table->unsignedSmallInteger('period_year')->nullable(false)->change();
        });
    }
};
