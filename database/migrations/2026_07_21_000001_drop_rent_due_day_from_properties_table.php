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
        if (! Schema::hasColumn('properties', 'rent_due_day')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('rent_due_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('properties', 'rent_due_day')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedTinyInteger('rent_due_day')->nullable()->after('currency');
        });
    }
};
