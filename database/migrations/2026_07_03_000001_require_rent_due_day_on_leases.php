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
        DB::table('leases')
            ->whereNull('rent_due_day')
            ->update([
                'rent_due_day' => DB::raw($this->dayFromStartDateExpression()),
            ]);

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

    private function dayFromStartDateExpression(): string
    {
        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => 'COALESCE(DAY(start_date), 1)',
            'pgsql' => 'COALESCE(EXTRACT(DAY FROM start_date), 1)',
            'sqlite' => "COALESCE(CAST(strftime('%d', start_date) AS INTEGER), 1)",
            default => '1',
        };
    }
};
