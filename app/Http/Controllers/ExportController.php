<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Lease;
use App\Models\Property;
use App\Models\RentPayment;
use App\Models\Team;
use App\Models\UtilityBill;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * @var list<string>
     */
    private const DATASETS = [
        'properties',
        'leases',
        'payments',
        'expenses',
        'utilities',
    ];

    public function index(Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [Property::class, $currentTeam]);

        return Inertia::render('exports/index');
    }

    public function download(Team $currentTeam, string $dataset): StreamedResponse
    {
        abort_unless(in_array($dataset, self::DATASETS, true), 404);

        return match ($dataset) {
            'properties' => $this->properties($currentTeam),
            'leases' => $this->leases($currentTeam),
            'payments' => $this->payments($currentTeam),
            'expenses' => $this->expenses($currentTeam),
            'utilities' => $this->utilities($currentTeam),
        };
    }

    private function properties(Team $currentTeam): StreamedResponse
    {
        Gate::authorize('viewAny', [Property::class, $currentTeam]);

        $today = today();

        $rows = Property::query()
            ->with([
                'leases' => fn ($query) => $query
                    ->select(['id', 'property_id', 'renter_id', 'start_date', 'end_date'])
                    ->with('renter:id,name')
                    ->orderByDesc('start_date'),
            ])
            ->whereBelongsTo($currentTeam)
            ->orderBy('name')
            ->get()
            ->map(function (Property $property) use ($today) {
                $activeLease = $property->leases->first(
                    fn (Lease $lease) => $lease->start_date->lte($today)
                        && ($lease->end_date === null || $lease->end_date->gte($today)),
                );

                return [
                    $property->id,
                    $property->name,
                    $this->exportLabel('property_type', $property->type),
                    $this->exportLabel('country', $property->country),
                    $property->city,
                    $property->county_or_sector,
                    $property->address_line,
                    $property->postal_code,
                    $property->rooms,
                    $property->usable_area_sqm,
                    $property->total_area_sqm,
                    $property->floor,
                    $property->total_floors,
                    $this->exportLabel('occupancy_status', $activeLease ? 'occupied' : 'available'),
                    $activeLease?->renter?->name,
                    $property->monthly_rent_amount,
                    $property->currency,
                    $property->deposit_amount,
                    $property->notes,
                ];
            });

        return $this->csv(
            $currentTeam,
            'properties',
            [
                __('exports.columns.id'),
                __('exports.columns.property_name'),
                __('exports.columns.property_type'),
                __('exports.columns.country'),
                __('exports.columns.city'),
                __('exports.columns.county_or_sector'),
                __('exports.columns.address'),
                __('exports.columns.postal_code'),
                __('exports.columns.rooms'),
                __('exports.columns.usable_area_sqm'),
                __('exports.columns.total_area_sqm'),
                __('exports.columns.floor'),
                __('exports.columns.total_floors'),
                __('exports.columns.occupancy_status'),
                __('exports.columns.renter_name'),
                __('exports.columns.monthly_rent'),
                __('exports.columns.currency'),
                __('exports.columns.deposit'),
                __('exports.columns.notes'),
            ],
            $rows,
        );
    }

    private function leases(Team $currentTeam): StreamedResponse
    {
        Gate::authorize('viewAny', [Lease::class, $currentTeam]);

        $rows = Lease::query()
            ->with(['property', 'renter'])
            ->whereBelongsTo($currentTeam)
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (Lease $lease) => [
                $lease->id,
                $lease->property->name,
                $lease->renter->name,
                $lease->renter->email,
                $lease->renter->phone,
                $lease->start_date,
                $lease->end_date,
                $lease->monthly_rent_amount,
                $lease->currency,
                $lease->rent_due_day,
                $lease->deposit_amount,
                $this->exportLabel('lease_status', $lease->computedStatus()),
                $lease->notes,
            ]);

        return $this->csv(
            $currentTeam,
            'leases',
            [
                __('exports.columns.id'),
                __('exports.columns.property_name'),
                __('exports.columns.renter_name'),
                __('exports.columns.renter_email'),
                __('exports.columns.renter_phone'),
                __('exports.columns.start_date'),
                __('exports.columns.end_date'),
                __('exports.columns.monthly_rent'),
                __('exports.columns.currency'),
                __('exports.columns.rent_due_day'),
                __('exports.columns.deposit'),
                __('exports.columns.status'),
                __('exports.columns.notes'),
            ],
            $rows,
        );
    }

    private function payments(Team $currentTeam): StreamedResponse
    {
        Gate::authorize('viewAny', [RentPayment::class, $currentTeam]);

        $rows = RentPayment::query()
            ->with(['property', 'renter'])
            ->whereBelongsTo($currentTeam)
            ->orderByDesc('payment_date')
            ->get()
            ->map(fn (RentPayment $payment) => [
                $payment->id,
                $payment->property->name,
                $payment->renter->name,
                $this->exportLabel('payment_type', $payment->payment_type),
                $payment->amount,
                $payment->currency,
                $payment->payment_date,
                $payment->period_month,
                $payment->period_year,
                $this->exportLabel('payment_method', $payment->method),
                $this->exportLabel('payment_status', $payment->status),
                $payment->notes,
            ]);

        return $this->csv(
            $currentTeam,
            'payments',
            [
                __('exports.columns.id'),
                __('exports.columns.property_name'),
                __('exports.columns.renter_name'),
                __('exports.columns.payment_type'),
                __('exports.columns.amount'),
                __('exports.columns.currency'),
                __('exports.columns.payment_date'),
                __('exports.columns.period_month'),
                __('exports.columns.period_year'),
                __('exports.columns.payment_method'),
                __('exports.columns.status'),
                __('exports.columns.notes'),
            ],
            $rows,
        );
    }

    private function expenses(Team $currentTeam): StreamedResponse
    {
        Gate::authorize('viewAny', [Expense::class, $currentTeam]);

        $rows = Expense::query()
            ->with(['property', 'lease.renter'])
            ->whereBelongsTo($currentTeam)
            ->orderByDesc('expense_date')
            ->get()
            ->map(fn (Expense $expense) => [
                $expense->id,
                $expense->property->name,
                $expense->lease?->renter?->name,
                $expense->title,
                $this->exportLabel('expense_category', $expense->category),
                $expense->amount,
                $expense->currency,
                $expense->expense_date,
                $this->exportLabel('expense_party', $expense->paid_by),
                $this->exportLabel('expense_party', $expense->responsible_party),
                $this->expenseSettlementLabel($expense),
                $this->exportLabel('expense_status', $expense->status),
                $expense->settled_at,
                $expense->notes,
            ]);

        return $this->csv(
            $currentTeam,
            'expenses',
            [
                __('exports.columns.id'),
                __('exports.columns.property_name'),
                __('exports.columns.renter_name'),
                __('exports.columns.title'),
                __('exports.columns.category'),
                __('exports.columns.amount'),
                __('exports.columns.currency'),
                __('exports.columns.expense_date'),
                __('exports.columns.paid_by'),
                __('exports.columns.responsible_party'),
                __('exports.columns.settlement_type'),
                __('exports.columns.status'),
                __('exports.columns.settled_at'),
                __('exports.columns.notes'),
            ],
            $rows,
        );
    }

    private function utilities(Team $currentTeam): StreamedResponse
    {
        Gate::authorize('viewAny', [UtilityBill::class, $currentTeam]);

        $rows = UtilityBill::query()
            ->with(['utilityAccount', 'property', 'lease.renter'])
            ->whereBelongsTo($currentTeam)
            ->orderByDesc('issue_date')
            ->get()
            ->map(fn (UtilityBill $bill) => [
                $bill->id,
                $bill->property->name,
                $bill->lease?->renter?->name,
                $bill->utilityAccount->provider_name,
                $this->exportLabel('utility_service_type', $bill->utilityAccount->service_type->value),
                $bill->utilityAccount->account_identifier,
                $bill->invoice_number,
                $bill->provider_invoice_id,
                $bill->payment_code,
                $bill->billing_period_start,
                $bill->billing_period_end,
                $bill->issue_date,
                $bill->due_date,
                $this->minorToDecimal($bill->amount_minor),
                $bill->previous_balance_minor === null
                    ? null
                    : $this->minorToDecimal($bill->previous_balance_minor),
                $bill->total_due_minor === null
                    ? null
                    : $this->minorToDecimal($bill->total_due_minor),
                $bill->currency,
                $this->exportLabel('utility_bill_status', $bill->status->value),
                $this->exportLabel('utility_party', $bill->responsible_party?->value),
                $this->exportLabel('utility_party', $bill->paid_by?->value),
                $bill->paid_on,
                $bill->notes,
            ]);

        return $this->csv(
            $currentTeam,
            'utilities',
            [
                __('exports.columns.id'),
                __('exports.columns.property_name'),
                __('exports.columns.renter_name'),
                __('exports.columns.provider'),
                __('exports.columns.service_type'),
                __('exports.columns.account_identifier'),
                __('exports.columns.invoice_number'),
                __('exports.columns.provider_invoice_id'),
                __('exports.columns.payment_code'),
                __('exports.columns.billing_period_start'),
                __('exports.columns.billing_period_end'),
                __('exports.columns.issue_date'),
                __('exports.columns.due_date'),
                __('exports.columns.invoice_amount'),
                __('exports.columns.previous_balance'),
                __('exports.columns.total_due'),
                __('exports.columns.currency'),
                __('exports.columns.status'),
                __('exports.columns.responsible_party'),
                __('exports.columns.paid_by'),
                __('exports.columns.paid_on'),
                __('exports.columns.notes'),
            ],
            $rows,
        );
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    private function csv(Team $currentTeam, string $dataset, array $headers, iterable $rows): StreamedResponse
    {
        $filename = sprintf(
            'rentier-%s-%s-%s.csv',
            $dataset,
            $currentTeam->slug,
            now()->format('Y-m-d'),
        );

        return response()->streamDownload(function () use ($headers, $rows): void {
            $stream = fopen('php://output', 'w');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers, ';', '"', '');

            foreach ($rows as $row) {
                fputcsv(
                    $stream,
                    array_map(fn (mixed $value) => $this->csvCell($value), $row),
                    ';',
                    '"',
                    '',
                );
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function csvCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        $cell = (string) $value;

        if ($cell !== '' && ! is_numeric($cell) && in_array($cell[0], ['=', '+', '-', '@'], true)) {
            return "'".$cell;
        }

        return $cell;
    }

    private function expenseSettlementLabel(Expense $expense): ?string
    {
        if ($expense->settlement_type === 'reimburse') {
            if ($expense->paid_by === 'owner' && $expense->responsible_party === 'tenant') {
                return __('exports.values.settlement_context.recover_from_renter');
            }

            if ($expense->paid_by === 'tenant' && $expense->responsible_party === 'owner') {
                return __('exports.values.settlement_context.reimburse_renter');
            }
        }

        return $this->exportLabel('settlement_type', $expense->settlement_type);
    }

    private function exportLabel(string $group, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $key = "exports.values.{$group}.{$value}";
        $translated = __($key);

        return $translated === $key ? $value : $translated;
    }

    private function minorToDecimal(int $amountMinor): string
    {
        return number_format($amountMinor / 100, 2, '.', '');
    }
}
