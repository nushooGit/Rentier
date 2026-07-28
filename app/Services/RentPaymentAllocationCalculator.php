<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Lease;
use App\Models\RentPayment;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class RentPaymentAllocationCalculator
{
    /**
     * @return array{months: array<string, array<string, mixed>>, payments: array<int, array<string, mixed>>}
     */
    public function forLease(Lease $lease, ?CarbonInterface $date = null): array
    {
        $date = $this->localCalendarDate($date);
        $leaseStart = $this->monthStart($lease->start_date);
        $leaseEnd = $lease->end_date ? $this->monthStart($lease->end_date) : null;
        $expectedCents = $this->moneyToCents($lease->monthly_rent_amount);
        $months = [];

        $viewedMonth = $date->copy()->startOfMonth();

        if ($this->monthIsEligible($viewedMonth, $leaseStart, $leaseEnd)) {
            $this->ensureMonth($months, $lease, $viewedMonth, $expectedCents);
        }

        foreach ($this->rentDeductionCentsByMonth($lease) as $periodKey => $deductionCents) {
            $month = Carbon::createFromFormat('Y-m-d', $periodKey.'-01', config('app.timezone'))->startOfMonth();

            if ($this->monthIsEligible($month, $leaseStart, $leaseEnd)) {
                $this->ensureMonth($months, $lease, $month, $expectedCents);
                $months[$periodKey]['rent_deduction_cents'] = $deductionCents;
            }
        }

        $paymentResults = [];

        foreach ($this->rentPayments($lease) as $payment) {
            $remainingCents = $this->moneyToCents($payment->amount);
            $allocatedCents = 0;
            $breakdown = [];
            $selectedMonth = Carbon::create((int) $payment->period_year, (int) $payment->period_month, 1, 0, 0, 0, config('app.timezone'))->startOfMonth();
            $month = $selectedMonth->lessThan($leaseStart) ? $leaseStart->copy() : $selectedMonth->copy();
            $guard = 0;

            while ($remainingCents > 0 && $this->monthIsEligible($month, $leaseStart, $leaseEnd)) {
                $periodKey = $this->periodKey($month);
                $this->ensureMonth($months, $lease, $month, $expectedCents);
                $neededCents = max(
                    $months[$periodKey]['expected_cents']
                    - $months[$periodKey]['rent_deduction_cents']
                    - $months[$periodKey]['cash_allocated_cents'],
                    0,
                );

                if ($neededCents > 0) {
                    $allocationCents = min($remainingCents, $neededCents);
                    $months[$periodKey]['cash_allocated_cents'] += $allocationCents;
                    $remainingCents -= $allocationCents;
                    $allocatedCents += $allocationCents;

                    $breakdown[] = [
                        'period_key' => $periodKey,
                        'period_date' => $months[$periodKey]['period_date'],
                        'period_label' => $this->periodLabel($month),
                        'amount' => $this->centsToDecimal($allocationCents),
                    ];
                }

                $month->addMonthNoOverflow()->startOfMonth();
                $guard++;

                if ($leaseEnd === null && $guard > 1200) {
                    break;
                }
            }

            $paymentResults[$payment->id] = [
                'payment_id' => $payment->id,
                'breakdown' => $breakdown,
                'total_allocated' => $this->centsToDecimal($allocatedCents),
                'unallocated_amount' => $this->centsToDecimal($remainingCents),
            ];
        }

        ksort($months);

        return [
            'months' => collect($months)
                ->map(fn (array $month) => $this->finalizeMonth($month, $date))
                ->all(),
            'payments' => $paymentResults,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function paymentAllocation(RentPayment $payment, ?CarbonInterface $date = null): ?array
    {
        if (($payment->payment_type ?? 'rent') === 'guarantee') {
            return null;
        }

        $allocation = $this->forLease($payment->lease, $date);

        return $allocation['payments'][$payment->id] ?? [
            'payment_id' => $payment->id,
            'breakdown' => [],
            'total_allocated' => '0.00',
            'unallocated_amount' => $this->centsToDecimal($this->moneyToCents($payment->amount)),
        ];
    }

    private function ensureMonth(array &$months, Lease $lease, CarbonInterface $month, int $expectedCents): void
    {
        $periodKey = $this->periodKey($month);

        if (isset($months[$periodKey])) {
            return;
        }

        $months[$periodKey] = [
            'period_key' => $periodKey,
            'period_date' => $month->toDateString(),
            'period_label' => $this->periodLabel($month),
            'expected_cents' => $expectedCents,
            'rent_deduction_cents' => 0,
            'cash_allocated_cents' => 0,
            'due_date' => $this->dueDateForMonth($lease, $month)->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function finalizeMonth(array $month, CarbonInterface $date): array
    {
        $coveredCents = min($month['expected_cents'], $month['rent_deduction_cents'] + $month['cash_allocated_cents']);
        $remainingCents = max($month['expected_cents'] - $coveredCents, 0);
        $periodDate = Carbon::parse($month['period_date'], config('app.timezone'))->startOfMonth();
        $currentMonth = $this->localCalendarDate($date)->startOfMonth();
        $dueDate = Carbon::parse($month['due_date'], config('app.timezone'))->startOfDay();
        $fullyPaid = $month['expected_cents'] > 0 && $remainingCents === 0;
        $partial = ! $fullyPaid && $coveredCents > 0;

        return [
            'period_key' => $month['period_key'],
            'period_date' => $month['period_date'],
            'period_label' => $month['period_label'],
            'expected_amount' => $this->centsToDecimal($month['expected_cents']),
            'rent_deduction_amount' => $this->centsToDecimal($month['rent_deduction_cents']),
            'cash_allocated' => $this->centsToDecimal($month['cash_allocated_cents']),
            'total_covered' => $this->centsToDecimal($coveredCents),
            'remaining_amount' => $this->centsToDecimal($remainingCents),
            'fully_paid' => $fullyPaid,
            'partial' => $partial,
            'overdue' => $remainingCents > 0 && $date->isAfter($dueDate),
            'paid_in_advance' => $periodDate->greaterThan($currentMonth) && $coveredCents > 0,
            'due_date' => $month['due_date'],
        ];
    }

    /**
     * @return iterable<int, RentPayment>
     */
    private function rentPayments(Lease $lease): iterable
    {
        return RentPayment::query()
            ->where('lease_id', $lease->id)
            ->whereNotNull('period_month')
            ->whereNotNull('period_year')
            ->where(function ($query) {
                $query
                    ->where('payment_type', 'rent')
                    ->orWhereNull('payment_type');
            })
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    private function rentDeductionCentsByMonth(Lease $lease): array
    {
        $deductions = [];

        Expense::query()
            ->where('status', '!=', 'cancelled')
            ->where('paid_by', 'tenant')
            ->where('responsible_party', 'owner')
            ->where('settlement_type', 'deduct_from_rent')
            ->where(function ($query) use ($lease) {
                $query
                    ->where('lease_id', $lease->id)
                    ->orWhere(function ($query) use ($lease) {
                        $query
                            ->whereNull('lease_id')
                            ->where('property_id', $lease->property_id);
                    });
            })
            ->get()
            ->each(function (Expense $expense) use (&$deductions) {
                $periodKey = $this->periodKey($expense->expense_date);
                $deductions[$periodKey] = ($deductions[$periodKey] ?? 0) + $this->moneyToCents($expense->amount);
            });

        return $deductions;
    }

    private function monthIsEligible(CarbonInterface $month, CarbonInterface $leaseStart, ?CarbonInterface $leaseEnd): bool
    {
        return ! $month->lessThan($leaseStart)
            && ($leaseEnd === null || ! $month->greaterThan($leaseEnd));
    }

    private function dueDateForMonth(Lease $lease, CarbonInterface $month): CarbonInterface
    {
        $dueDate = $this->monthStart($month);

        return $dueDate->setDay(min($lease->rent_due_day, $dueDate->daysInMonth));
    }

    private function monthStart(CarbonInterface $date): CarbonInterface
    {
        return Carbon::instance($date->toDateTime())->timezone(config('app.timezone'))->startOfMonth();
    }

    private function localCalendarDate(?CarbonInterface $date = null): CarbonInterface
    {
        return ($date instanceof CarbonInterface
            ? Carbon::instance($date->toDateTime())->timezone(config('app.timezone'))
            : Carbon::today(config('app.timezone')))
            ->startOfDay();
    }

    private function periodKey(CarbonInterface $month): string
    {
        return $month->format('Y-m');
    }

    private function periodLabel(CarbonInterface $month): string
    {
        return ucfirst($month->locale('ro')->translatedFormat('F Y'));
    }

    private function moneyToCents(float|int|string|null $amount): int
    {
        $normalized = number_format((float) ($amount ?? 0), 2, '.', '');

        return (int) str_replace('.', '', $normalized);
    }

    private function centsToDecimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return $sign.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
