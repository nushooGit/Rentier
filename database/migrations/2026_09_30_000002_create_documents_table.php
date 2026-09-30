<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lease_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 64)->index();
            $table->date('document_date');
            $table->date('expires_on')->nullable()->index();
            $table->string('disk', 64)->default('local');
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type', 191);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->index(['team_id', 'document_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
