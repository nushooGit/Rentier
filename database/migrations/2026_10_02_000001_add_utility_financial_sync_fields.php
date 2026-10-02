<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->string('paid_by', 16)->nullable()->after('status');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table
                ->foreignId('utility_bill_id')
                ->nullable()
                ->unique()
                ->after('lease_id')
                ->constrained('utility_bills')
                ->cascadeOnDelete();
        });


    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('utility_bill_id');
        });

        Schema::table('utility_bills', function (Blueprint $table) {
            $table->dropColumn('paid_by');
        });
    }
};
