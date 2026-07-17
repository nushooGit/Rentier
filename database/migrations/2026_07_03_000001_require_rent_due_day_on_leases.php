<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->backfillRentDueDay();

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE leases MODIFY rent_due_day TINYINT UNSIGNED NOT NULL DEFAULT 1');
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE leases ALTER COLUMN rent_due_day SET DEFAULT 1');
            DB::statement('ALTER TABLE leases ALTER COLUMN rent_due_day SET NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE leases MODIFY rent_due_day TINYINT UNSIGNED NULL DEFAULT NULL');
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE leases ALTER COLUMN rent_due_day DROP NOT NULL');
            DB::statement('ALTER TABLE leases ALTER COLUMN rent_due_day DROP DEFAULT');
        }
    }

    private function backfillRentDueDay(): void
    {
        $expression = match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::raw('COALESCE(DAY(start_date), 1)'),
            'pgsql' => DB::raw('COALESCE(EXTRACT(DAY FROM start_date), 1)'),
            'sqlite' => DB::raw("COALESCE(CAST(strftime('%d', start_date) AS INTEGER), 1)"),
            default => DB::raw('1'),
        };

        DB::table('leases')
            ->whereNull('rent_due_day')
            ->update([
                'rent_due_day' => $expression,
            ]);
    }
};
