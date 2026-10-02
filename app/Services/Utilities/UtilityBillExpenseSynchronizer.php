<?php

namespace App\Services\Utilities;

use App\Enums\UtilityBillStatus;
use App\Enums\UtilityPaidBy;
use App\Enums\UtilityResponsibleParty;
use App\Models\Expense;
use App\Models\UtilityBill;

class UtilityBillExpenseSynchronizer
{
    public function sync(UtilityBill $bill): Expense
    {
        $bill->loadMissing('utilityAccount');

        $responsibleParty = $this->expenseParty(
            $bill->utilityAccount->responsible_party,
        );

        $paidBy = $bill->status === UtilityBillStatus::Paid
            ? $this->expenseParty(
                $bill->paid_by
                    ?? UtilityPaidBy::from($bill->utilityAccount->responsible_party->value),
            )
            : $responsibleParty;

        $crossPartyPayment = $bill->status === UtilityBillStatus::Paid
            && $paidBy !== $responsibleParty;

        $expense = Expense::query()->firstOrNew([
            'utility_bill_id' => $bill->id,
        ]);

        $amount = number_format($bill->amount_minor / 100, 2, '.', '');
        $preserveSettledState = $crossPartyPayment
            && $expense->exists
            && $expense->settlement_type === 'reimburse'
            && $expense->settled_at !== null
            && $expense->paid_by === $paidBy
            && $expense->responsible_party === $responsibleParty
            && $expense->currency === $bill->currency
            && number_format((float) $expense->amount, 2, '.', '') === $amount;

        $expense->fill([
            'team_id' => $bill->team_id,
            'property_id' => $bill->property_id,
            'lease_id' => $bill->lease_id,
            'title' => $bill->utilityAccount->provider_name.' · '.$bill->invoice_number,
            'category' => 'utilities',
            'amount' => $amount,
            'currency' => $bill->currency,
            'expense_date' => $bill->issue_date->toDateString(),
            'paid_by' => $paidBy,
            'responsible_party' => $responsibleParty,
            'settlement_type' => $crossPartyPayment ? 'reimburse' : 'none',
            'settled_at' => $preserveSettledState ? $expense->settled_at : null,
            'status' => match (true) {
                $bill->status === UtilityBillStatus::Unpaid => 'pending',
                $crossPartyPayment && ! $preserveSettledState => 'reimbursable',
                default => 'paid',
            },
            'notes' => $bill->notes,
        ]);

        $expense->save();

        return $expense;
    }

    private function expenseParty(
        UtilityResponsibleParty|UtilityPaidBy $party,
    ): string {
        return $party->value === 'renter' ? 'tenant' : 'owner';
    }
}
