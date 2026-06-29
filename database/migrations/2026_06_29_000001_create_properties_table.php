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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('country')->default('Romania');
            $table->string('city');
            $table->string('county_or_sector')->nullable();
            $table->string('address_line');
            $table->string('postal_code')->nullable();
            $table->unsignedSmallInteger('rooms')->nullable();
            $table->decimal('usable_area_sqm', 10, 2)->nullable();
            $table->smallInteger('floor')->nullable();
            $table->smallInteger('total_floors')->nullable();
            $table->string('status')->default('available');
            $table->decimal('monthly_rent_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('RON');
            $table->unsignedTinyInteger('rent_due_day')->nullable();
            $table->decimal('deposit_amount', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
