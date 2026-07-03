<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\RentPayment;
use Carbon\CarbonInterface;

class LeaseRentStatusCalculator
{
    /**
     * @return array{key: string, label: string, days: int|null, due_date: string, expected_amount: string, collected_amount: string, remaining_amount: string}
     */
    public function forLease(Lease $lease, ?CarbonInterface $date = null): array
    {
        $date ??= today();
        $expectedAmount = (float) $lease->monthly_rent_amount;
        $collectedAmount = $this->collectedAmountForCurrentMonth($lease, $date);
        $remainingAmount = max($expectedAmount - $collectedAmount, 0);
        $dueDate = $this->dueDateForMonth($lease, $date);

        if ($collectedAmount >= $expectedAmount) {
            return $this->rentPaymentStatus('paid', 'Plătită luna asta', null, $dueDate, $expectedAmount, $collectedAmount, $remainingAmount);
        }

        if ($collectedAmount > 0) {
            return $this->rentPaymentStatus('partial', 'Plătită parțial', null, $dueDate, $expectedAmount, $collectedAmount, $remainingAmount);
        }

        if ($date->isSameDay($dueDate)) {
            return $this->rentPaymentStatus('due_today', 'Scadentă azi', 0, $dueDate, $expectedAmount, $collectedAmount, $remainingAmount);
        }

        if ($date->isBefore($dueDate)) {
            $days = (int) $date->diffInDays($dueDate);

            return $this->rentPaymentStatus(
                'upcoming',
                "Mai sunt {$days} zile până la plată",
                $days,
                $dueDate,
                $expectedAmount,
                $collectedAmount,
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

    public function dueDateForMonth(Lease $lease, CarbonInterface $date): CarbonInterface
    {
        $dueDate = $date->copy()->startOfMonth();

        return $dueDate->setDay(min($lease->rent_due_day, $dueDate->daysInMonth));
    }

    /**
     * @return array{key: string, label: string, days: int|null, due_date: string, expected_amount: string, collected_amount: string, remaining_amount: string}
     */
    private function rentPaymentStatus(
        string $key,
        string $label,
        ?int $days,
        CarbonInterface $dueDate,
        float $expectedAmount,
        float $collectedAmount,
        float $remainingAmount,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'days' => $days,
            'due_date' => $dueDate->toDateString(),
            'expected_amount' => number_format($expectedAmount, 2, '.', ''),
            'collected_amount' => number_format($collectedAmount, 2, '.', ''),
            'remaining_amount' => number_format($remainingAmount, 2, '.', ''),
        ];
    }
}
