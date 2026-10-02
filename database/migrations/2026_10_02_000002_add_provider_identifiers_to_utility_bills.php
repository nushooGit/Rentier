<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->string('provider_invoice_id', 191)->nullable()->after('invoice_number');
            $table->string('payment_code', 191)->nullable()->after('provider_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->dropColumn(['provider_invoice_id', 'payment_code']);
        });
    }
};
