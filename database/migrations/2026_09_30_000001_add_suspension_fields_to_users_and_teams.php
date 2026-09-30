<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->index();
            $table->text('suspension_reason')->nullable();
            $table->foreignId('suspended_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reactivated_at')->nullable();
            $table->foreignId('reactivated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->index();
            $table->text('suspension_reason')->nullable();
            $table->foreignId('suspended_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reactivated_at')->nullable();
            $table->foreignId('reactivated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suspended_by_user_id');
            $table->dropConstrainedForeignId('reactivated_by_user_id');
            $table->dropColumn([
                'suspended_at',
                'suspension_reason',
                'reactivated_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suspended_by_user_id');
            $table->dropConstrainedForeignId('reactivated_by_user_id');
            $table->dropColumn([
                'suspended_at',
                'suspension_reason',
                'reactivated_at',
            ]);
        });
    }
};
