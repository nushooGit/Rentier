<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->bigInteger('previous_balance_minor')->nullable()->after('amount_minor');
            $table->bigInteger('total_due_minor')->nullable()->after('previous_balance_minor');
        });
    }

    public function down(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->dropColumn(['previous_balance_minor', 'total_due_minor']);
        });
    }
};
