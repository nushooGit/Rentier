<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Lease;
use App\Models\RentPayment;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class LeaseRentStatusCalculator
{
    /**
     * @return array{key: string, label: string, days: int|null, due_date: string, expected_amount: string, collected_amount: string, rent_deduction_amount: string, covered_amount: string, remaining_amount: string}
     */
    public function forLease(Lease $lease, ?CarbonInterface $date = null): array
    {
        $date = $this->localCalendarDate($date);
        $expectedAmount = (float) $lease->monthly_rent_amount;
        $collectedAmount = $this->collectedAmountForCurrentMonth($lease, $date);
        $rentDeductionAmount = $this->rentDeductionAmountForCurrentMonth($lease, $date);
        $coveredAmount = $collectedAmount + $rentDeductionAmount;
        $remainingAmount = max($expectedAmount - $coveredAmount, 0);
        $dueDate = $this->dueDateForMonth($lease, $date);

        if ($coveredAmount >= $expectedAmount) {
            return $this->rentPaymentStatus('paid', 'Plătită luna asta', null, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount);
        }

        if ($coveredAmount > 0) {
            return $this->rentPaymentStatus('partial', 'Plătită parțial', null, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount);
        }

        if ($date->isSameDay($dueDate)) {
            return $this->rentPaymentStatus('due_today', 'Scadentă azi', 0, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount);
        }

        if ($date->isBefore($dueDate)) {
            $days = (int) $date->diffInDays($dueDate);
            $label = $days === 1
                ? 'Mai este 1 zi până la plată'
                : "Mai sunt {$days} zile până la plată";

            return $this->rentPaymentStatus(
                'upcoming',
                $label,
                $days,
                $dueDate,
                $expectedAmount,
                $collectedAmount,
                $rentDeductionAmount,
                $coveredAmount,
                $remainingAmount,
            );
        }

        $days = (int) $dueDate->diffInDays($date);

        return $this->rentPaymentStatus(
            'overdue',
            "Întârziată cu {$days} zile",
            $days,
            $dueDate,
            $expectedAmount,
            $collectedAmount,
            $rentDeductionAmount,
            $coveredAmount,
            $remainingAmount,
        );
    }

    public function collectedAmountForCurrentMonth(Lease $lease, CarbonInterface $date): float
    {
        return (float) RentPayment::query()
            ->where('lease_id', $lease->id)
            ->where('period_year', $date->year)
            ->where('period_month', $date->month)
            ->sum('amount');
    }

    public function rentDeductionAmountForCurrentMonth(Lease $lease, CarbonInterface $date): float
    {
        return (float) Expense::query()
            ->where('status', '!=', 'cancelled')
            ->where('paid_by', 'tenant')
            ->where('responsible_party', 'owner')
            ->where('settlement_type', 'deduct_from_rent')
            ->whereYear('expense_date', $date->year)
            ->whereMonth('expense_date', $date->month)
            ->where(function ($query) use ($lease) {
                $query
                    ->where('lease_id', $lease->id)
                    ->orWhere(function ($query) use ($lease) {
                        $query
                            ->whereNull('lease_id')
                            ->where('property_id', $lease->property_id);
                    });
            })
            ->sum('amount');
    }

    public function dueDateForMonth(Lease $lease, CarbonInterface $date): CarbonInterface
    {
        $dueDate = $this->localCalendarDate($date)->startOfMonth();

        return $dueDate->setDay(min($lease->rent_due_day, $dueDate->daysInMonth));
    }

    private function localCalendarDate(?CarbonInterface $date = null): CarbonInterface
    {
        $timezone = config('app.timezone');

        return ($date instanceof CarbonInterface
            ? Carbon::instance($date->toDateTime())->timezone($timezone)
            : Carbon::today($timezone))
            ->startOfDay();
    }

    /**
     * @return array{key: string, label: string, days: int|null, due_date: string, expected_amount: string, collected_amount: string, rent_deduction_amount: string, covered_amount: string, remaining_amount: string}
     */
    private function rentPaymentStatus(
        string $key,
        string $label,
        ?int $days,
        CarbonInterface $dueDate,
        float $expectedAmount,
        float $collectedAmount,
        float $rentDeductionAmount,
        float $coveredAmount,
        float $remainingAmount,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'days' => $days,
            'due_date' => $dueDate->toDateString(),
            'expected_amount' => number_format($expectedAmount, 2, '.', ''),
            'collected_amount' => number_format($collectedAmount, 2, '.', ''),
            'rent_deduction_amount' => number_format($rentDeductionAmount, 2, '.', ''),
            'covered_amount' => number_format($coveredAmount, 2, '.', ''),
            'remaining_amount' => number_format($remainingAmount, 2, '.', ''),
        ];
    }
}
