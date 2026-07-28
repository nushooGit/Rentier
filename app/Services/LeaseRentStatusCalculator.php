<?php

namespace App\Services;

use App\Models\Lease;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class LeaseRentStatusCalculator
{
    public function __construct(private readonly RentPaymentAllocationCalculator $allocationCalculator) {}

    /**
     * @return array{key: string, label: string, days: int|null, due_date: string, expected_amount: string, collected_amount: string, rent_deduction_amount: string, covered_amount: string, remaining_amount: string, badges: array<int, array{key: string, label: string, tone: string}>, advance_notice: array{key: string, label: string, period_key: string, amount: string, expected_amount: string}|null, advance_notices: array<int, array{key: string, label: string, period_key: string, amount: string, expected_amount: string}>, advance_months: array<int, array<string, mixed>>}
     */
    public function forLease(Lease $lease, ?CarbonInterface $date = null): array
    {
        $date = $this->localCalendarDate($date);
        $allocation = $this->allocationCalculator->forLease($lease, $date);
        $periodKey = $date->format('Y-m');
        $month = $allocation['months'][$periodKey] ?? [
            'expected_amount' => $this->decimalString($lease->monthly_rent_amount),
            'cash_allocated' => '0.00',
            'rent_deduction_amount' => '0.00',
            'total_covered' => '0.00',
            'remaining_amount' => $this->decimalString($lease->monthly_rent_amount),
        ];

        $expectedAmount = $month['expected_amount'];
        $collectedAmount = $month['cash_allocated'];
        $rentDeductionAmount = $month['rent_deduction_amount'];
        $coveredAmount = $month['total_covered'];
        $remainingAmount = $month['remaining_amount'];
        $dueDate = $this->dueDateForMonth($lease, $date);
        $advanceMonths = $this->advanceMonths($allocation['months'], $date);
        $advanceNotices = $this->advanceNotices($advanceMonths, $lease->currency);
        $advanceNotice = $advanceNotices[0] ?? null;

        if ($this->compareMoney($coveredAmount, $expectedAmount) >= 0 && $this->compareMoney($expectedAmount, '0.00') > 0) {
            $label = $this->compareMoney($rentDeductionAmount, '0.00') > 0
                ? 'Chirie acoperită luna aceasta'
                : 'Plătită luna aceasta';

            return $this->rentPaymentStatus('paid', $label, null, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount, null, $advanceNotice, $advanceNotices, $advanceMonths);
        }

        if ($this->compareMoney($coveredAmount, '0.00') > 0) {
            $label = $this->compareMoney($rentDeductionAmount, '0.00') > 0
                ? 'Chirie acoperită parțial'
                : 'Plătită parțial';

            if ($date->isAfter($dueDate)) {
                $days = (int) $dueDate->diffInDays($date);
                $overdueLabel = $days === 1
                    ? 'Întârziată cu 1 zi'
                    : "Întârziată cu {$days} zile";

                return $this->rentPaymentStatus(
                    'partial_overdue',
                    $label,
                    $days,
                    $dueDate,
                    $expectedAmount,
                    $collectedAmount,
                    $rentDeductionAmount,
                    $coveredAmount,
                    $remainingAmount,
                    [
                        ['key' => 'partial', 'label' => 'Plătită parțial', 'tone' => 'partial'],
                        ['key' => 'overdue', 'label' => $overdueLabel, 'tone' => 'overdue'],
                    ],
                    $advanceNotice,
                    $advanceNotices,
                    $advanceMonths,
                );
            }

            return $this->rentPaymentStatus('partial', $label, null, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount, null, $advanceNotice, $advanceNotices, $advanceMonths);
        }

        if ($date->isSameDay($dueDate)) {
            return $this->rentPaymentStatus('due_today', 'Scadentă azi', 0, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount, null, $advanceNotice, $advanceNotices, $advanceMonths);
        }

        if ($date->isBefore($dueDate)) {
            $days = (int) $date->diffInDays($dueDate);
            $label = $days === 1
                ? 'Mai este 1 zi până la plată'
                : "Mai sunt {$days} zile până la plată";

            return $this->rentPaymentStatus('upcoming', $label, $days, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount, null, $advanceNotice, $advanceNotices, $advanceMonths);
        }

        $days = (int) $dueDate->diffInDays($date);

        return $this->rentPaymentStatus('overdue', "Întârziată cu {$days} zile", $days, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount, null, $advanceNotice, $advanceNotices, $advanceMonths);
    }

    public function dueDateForMonth(Lease $lease, CarbonInterface $date): CarbonInterface
    {
        $dueDate = $this->localCalendarDate($date)->startOfMonth();

        return $dueDate->setDay(min($lease->rent_due_day, $dueDate->daysInMonth));
    }

    private function localCalendarDate(?CarbonInterface $date = null): CarbonInterface
    {
        return ($date instanceof CarbonInterface
            ? Carbon::instance($date->toDateTime())->timezone(config('app.timezone'))
            : Carbon::today(config('app.timezone')))
            ->startOfDay();
    }

    /**
     * @return array{key: string, label: string, days: int|null, due_date: string, expected_amount: string, collected_amount: string, rent_deduction_amount: string, covered_amount: string, remaining_amount: string, badges: array<int, array{key: string, label: string, tone: string}>, advance_notice: array{key: string, label: string, period_key: string, amount: string, expected_amount: string}|null, advance_notices: array<int, array{key: string, label: string, period_key: string, amount: string, expected_amount: string}>, advance_months: array<int, array<string, mixed>>}
     */
    private function rentPaymentStatus(
        string $key,
        string $label,
        ?int $days,
        CarbonInterface $dueDate,
        string $expectedAmount,
        string $collectedAmount,
        string $rentDeductionAmount,
        string $coveredAmount,
        string $remainingAmount,
        ?array $badges = null,
        ?array $advanceNotice = null,
        array $advanceNotices = [],
        array $advanceMonths = [],
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'days' => $days,
            'due_date' => $dueDate->toDateString(),
            'expected_amount' => $expectedAmount,
            'collected_amount' => $collectedAmount,
            'rent_deduction_amount' => $rentDeductionAmount,
            'covered_amount' => $coveredAmount,
            'remaining_amount' => $remainingAmount,
            'badges' => $badges ?? [
                ['key' => $key, 'label' => $label, 'tone' => $key],
            ],
            'advance_notice' => $advanceNotice,
            'advance_notices' => $advanceNotices,
            'advance_months' => $advanceMonths,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $months
     * @return array<int, array<string, mixed>>
     */
    private function advanceMonths(array $months, CarbonInterface $date): array
    {
        $currentMonth = $this->localCalendarDate($date)->startOfMonth();

        return collect($months)
            ->filter(fn (array $month) => Carbon::parse($month['period_date'])->startOfMonth()->greaterThan($currentMonth)
                && $this->compareMoney($month['total_covered'], '0.00') > 0)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $advanceMonths
     * @return array<int, array{key: string, label: string, period_key: string, amount: string, expected_amount: string}>
     */
    private function advanceNotices(array $advanceMonths, string $currency): array
    {
        if ($advanceMonths === []) {
            return [];
        }

        $notices = [];
        $lastFullyPaid = collect($advanceMonths)->filter(fn (array $month) => $month['fully_paid'])->last();

        if (is_array($lastFullyPaid)) {
            $notices[] = [
                'key' => 'paid_through',
                'label' => 'Plătită în avans până în '.strtolower((string) $lastFullyPaid['period_label']),
                'period_key' => $lastFullyPaid['period_key'],
                'amount' => $lastFullyPaid['total_covered'],
                'expected_amount' => $lastFullyPaid['expected_amount'],
            ];
        }

        $partial = collect($advanceMonths)->first(fn (array $month) => $month['partial']);

        if (is_array($partial)) {
            $notices[] = [
                'key' => 'partial_advance',
                'label' => 'Avans pentru '.strtolower((string) $partial['period_label']).': '.$this->formatMoney($partial['total_covered'], $currency).' / '.$this->formatMoney($partial['expected_amount'], $currency),
                'period_key' => $partial['period_key'],
                'amount' => $partial['total_covered'],
                'expected_amount' => $partial['expected_amount'],
            ];
        }

        return $notices;
    }

    private function compareMoney(string $left, string $right): int
    {
        return $this->moneyToCents($left) <=> $this->moneyToCents($right);
    }

    private function moneyToCents(string $amount): int
    {
        return (int) str_replace('.', '', number_format((float) $amount, 2, '.', ''));
    }

    private function decimalString(float|int|string|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 2, '.', '');
    }

    private function formatMoney(string $amount, string $currency): string
    {
        return number_format((float) $amount, 0, ',', '.').' '.$currency;
    }
}
