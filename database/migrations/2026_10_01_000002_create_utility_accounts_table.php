<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lease_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_name', 191);
            $table->string('service_type', 32);
            $table->string('account_identifier', 191)->nullable();
            $table->string('responsible_party', 16)->default('owner');
            $table->string('status', 16)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'property_id']);
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_accounts');
    }
};
