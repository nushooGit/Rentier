<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('responsible_party')->default('owner')->after('paid_by');
            $table->string('settlement_type')->default('none')->after('responsible_party');
        });

        DB::table('expenses')->where('paid_by', 'landlord')->update(['paid_by' => 'owner']);
        DB::table('expenses')->where('paid_by', 'renter')->update(['paid_by' => 'tenant']);
        DB::table('expenses')->where('paid_by', 'other')->update(['paid_by' => 'owner']);
        DB::table('expenses')->whereNull('paid_by')->update(['paid_by' => 'owner']);
        DB::table('expenses')->whereNull('responsible_party')->update(['responsible_party' => 'owner']);
        DB::table('expenses')->whereNull('settlement_type')->update(['settlement_type' => 'none']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['responsible_party', 'settlement_type']);
        });
    }
};
