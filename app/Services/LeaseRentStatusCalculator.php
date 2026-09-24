<?php

namespace App\Services;

use App\Models\Lease;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * @phpstan-import-type FinalizedMonth from RentPaymentAllocationCalculator
 *
 * @phpstan-type Badge array{key: string, label: string, tone: string}
 * @phpstan-type AdvanceNotice array{key: string, label: string, period_key: string, amount: string, expected_amount: string}
 * @phpstan-type ArrearsSummary array{amount: string, month_count: int, months: list<FinalizedMonth>, oldest_due_date: string|null, days: int|null, has_partial: bool}
 * @phpstan-type RentStatus array{key: string, label: string, days: int|null, due_date: string, expected_amount: string, collected_amount: string, rent_deduction_amount: string, covered_amount: string, remaining_amount: string, arrears_amount: string, overdue_month_count: int, overdue_months: list<FinalizedMonth>, oldest_overdue_due_date: string|null, badges: list<Badge>, advance_notice: AdvanceNotice|null, advance_notices: list<AdvanceNotice>, advance_months: list<FinalizedMonth>}
 */
class LeaseRentStatusCalculator
{
    public function __construct(private readonly RentPaymentAllocationCalculator $allocationCalculator) {}

    /**
     * @return RentStatus
     */
    public function forLease(Lease $lease, ?CarbonInterface $date = null): array
    {
        $date = $this->localCalendarDate($date);
        $allocation = $this->allocationCalculator->forLease($lease, $date);
        $periodKey = $date->format('Y-m');
        $month = $allocation['months'][$periodKey] ?? [
            'expected_amount' => '0.00',
            'cash_allocated' => '0.00',
            'rent_deduction_amount' => '0.00',
            'total_covered' => '0.00',
            'remaining_amount' => '0.00',
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
        $arrears = $this->arrearsSummary($allocation['months'], $date);

        if ($this->compareMoney($coveredAmount, $expectedAmount) >= 0 && $this->compareMoney($expectedAmount, '0.00') > 0) {
            $label = $this->compareMoney($rentDeductionAmount, '0.00') > 0
                ? 'Chirie acoperită luna aceasta'
                : 'Plătită luna aceasta';

            return $this->rentPaymentStatus('paid', $label, null, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount, $lease->currency, $arrears, null, $advanceNotice, $advanceNotices, $advanceMonths);
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
                    $lease->currency,
                    $arrears,
                    [
                        ['key' => 'partial', 'label' => 'Plătită parțial', 'tone' => 'partial'],
                        ['key' => 'overdue', 'label' => $overdueLabel, 'tone' => 'overdue'],
                    ],
                    $advanceNotice,
                    $advanceNotices,
                    $advanceMonths,
                );
            }

            return $this->rentPaymentStatus('partial', $label, null, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount, $lease->currency, $arrears, null, $advanceNotice, $advanceNotices, $advanceMonths);
        }

        if ($date->isSameDay($dueDate)) {
            return $this->rentPaymentStatus('due_today', 'Scadentă azi', 0, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount, $lease->currency, $arrears, null, $advanceNotice, $advanceNotices, $advanceMonths);
        }

        if ($date->isBefore($dueDate)) {
            $days = (int) $date->diffInDays($dueDate);
            $label = $days === 1
                ? 'Mai este 1 zi până la plată'
                : "Mai sunt {$days} zile până la plată";

            return $this->rentPaymentStatus('upcoming', $label, $days, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount, $lease->currency, $arrears, null, $advanceNotice, $advanceNotices, $advanceMonths);
        }

        $days = (int) $dueDate->diffInDays($date);

        return $this->rentPaymentStatus('overdue', "Întârziată cu {$days} zile", $days, $dueDate, $expectedAmount, $collectedAmount, $rentDeductionAmount, $coveredAmount, $remainingAmount, $lease->currency, $arrears, null, $advanceNotice, $advanceNotices, $advanceMonths);
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
     * @param  list<Badge>|null  $badges
     * @param  ArrearsSummary  $arrears
     * @param  AdvanceNotice|null  $advanceNotice
     * @param  list<AdvanceNotice>  $advanceNotices
     * @param  list<FinalizedMonth>  $advanceMonths
     * @return RentStatus
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
        string $currency,
        array $arrears,
        ?array $badges = null,
        ?array $advanceNotice = null,
        array $advanceNotices = [],
        array $advanceMonths = [],
    ): array {
        if ($arrears['month_count'] > 0) {
            $hasPartialStatus = in_array($key, ['partial', 'partial_overdue'], true)
                || $arrears['has_partial'];
            $key = $hasPartialStatus ? 'partial_overdue' : 'overdue';
            $label = 'Restanță: '.$this->formatMoney($arrears['amount'], $currency);
            $days = $arrears['days'];
            $badges = [];

            if ($hasPartialStatus) {
                $badges[] = ['key' => 'partial', 'label' => 'Plătită parțial', 'tone' => 'partial'];
            }

            $badges[] = ['key' => 'arrears', 'label' => $label, 'tone' => 'overdue'];

            if ($arrears['month_count'] > 1) {
                $badges[] = [
                    'key' => 'overdue_months',
                    'label' => "{$arrears['month_count']} luni restante",
                    'tone' => 'overdue',
                ];
            }
        }

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
            'arrears_amount' => $arrears['amount'],
            'overdue_month_count' => $arrears['month_count'],
            'overdue_months' => $arrears['months'],
            'oldest_overdue_due_date' => $arrears['oldest_due_date'],
            'badges' => $badges ?? [
                ['key' => $key, 'label' => $label, 'tone' => $key],
            ],
            'advance_notice' => $advanceNotice,
            'advance_notices' => $advanceNotices,
            'advance_months' => $advanceMonths,
        ];
    }

    /**
     * @param  array<string, FinalizedMonth>  $months
     * @return ArrearsSummary
     */
    private function arrearsSummary(array $months, CarbonInterface $date): array
    {
        $overdueMonths = array_values(array_filter(
            $months,
            fn (array $month) => $month['overdue']
                && $this->compareMoney($month['remaining_amount'], '0.00') > 0,
        ));
        $arrearsCents = array_sum(array_map(
            fn (array $month) => $this->moneyToCents($month['remaining_amount']),
            $overdueMonths,
        ));
        $oldestDueDate = $overdueMonths[0]['due_date'] ?? null;

        return [
            'amount' => $this->centsToDecimal($arrearsCents),
            'month_count' => count($overdueMonths),
            'months' => $overdueMonths,
            'oldest_due_date' => $oldestDueDate,
            'days' => $oldestDueDate !== null
                ? (int) Carbon::parse($oldestDueDate, config('app.timezone'))->diffInDays($date)
                : null,
            'has_partial' => collect($overdueMonths)->contains(fn (array $month) => $month['partial']),
        ];
    }

    /**
     * @param  array<string, FinalizedMonth>  $months
     * @return list<FinalizedMonth>
     */
    private function advanceMonths(array $months, CarbonInterface $date): array
    {
        $currentMonth = $this->localCalendarDate($date)->startOfMonth();

        return array_values(collect($months)
            ->filter(fn (array $month) => Carbon::parse($month['period_date'])->startOfMonth()->greaterThan($currentMonth)
                && $this->compareMoney($month['total_covered'], '0.00') > 0)
            ->values()
            ->all());
    }

    /**
     * @param  list<FinalizedMonth>  $advanceMonths
     * @return list<AdvanceNotice>
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

    private function centsToDecimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function formatMoney(string $amount, string $currency): string
    {
        return number_format((float) $amount, 0, ',', '.').' '.$currency;
    }
}
