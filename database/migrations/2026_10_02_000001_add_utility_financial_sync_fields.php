<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        DB::table('utility_bills')
            ->orderBy('id')
            ->chunkById(100, function ($bills): void {
                $accounts = DB::table('utility_accounts')
                    ->whereIn('id', $bills->pluck('utility_account_id'))
                    ->get()
                    ->keyBy('id');

                foreach ($bills as $bill) {
                    $account = $accounts->get($bill->utility_account_id);

                    if ($account === null) {
                        continue;
                    }

                    $responsibleParty = $account->responsible_party === 'renter'
                        ? 'tenant'
                        : 'owner';
                    $isPaid = $bill->status === 'paid';

                    if ($isPaid) {
                        DB::table('utility_bills')
                            ->where('id', $bill->id)
                            ->update([
                                'paid_by' => $account->responsible_party,
                            ]);
                    }

                    DB::table('expenses')->insert([
                        'team_id' => $bill->team_id,
                        'property_id' => $bill->property_id,
                        'lease_id' => $bill->lease_id,
                        'utility_bill_id' => $bill->id,
                        'title' => $account->provider_name.' · '.$bill->invoice_number,
                        'category' => 'utilities',
                        'amount' => number_format(((int) $bill->amount_minor) / 100, 2, '.', ''),
                        'currency' => $bill->currency,
                        'expense_date' => $bill->issue_date,
                        'paid_by' => $responsibleParty,
                        'responsible_party' => $responsibleParty,
                        'settlement_type' => 'none',
                        'settled_at' => null,
                        'status' => $isPaid ? 'paid' : 'pending',
                        'notes' => $bill->notes,
                        'created_at' => $bill->created_at,
                        'updated_at' => $bill->updated_at,
                    ]);
                }
            });

    }

    public function down(): void
    {
        DB::table('expenses')
            ->whereNotNull('utility_bill_id')
            ->delete();

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('utility_bill_id');
        });

        Schema::table('utility_bills', function (Blueprint $table) {
            $table->dropColumn('paid_by');
        });
    }
};
