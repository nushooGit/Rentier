<?php

namespace App\Http\Requests\Expenses;

use App\Models\Expense;
use App\Models\Lease;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $expense = $this->route('expense');

        if ($expense instanceof Expense) {
            return Gate::allows('update', $expense);
        }

        $team = $this->route('current_team');

        return $team instanceof Team
            && Gate::allows('create', [Expense::class, $team]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $team = $this->route('current_team');
        $teamId = $team instanceof Team ? $team->id : null;

        return [
            'property_id' => [
                'required',
                'integer',
                Rule::exists('properties', 'id')->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'lease_id' => [
                'nullable',
                'integer',
                Rule::exists('leases', 'id')->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(['repairs', 'maintenance', 'utilities', 'renovation', 'taxes', 'other', 'insurance', 'admin'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'expense_date' => ['required', 'date'],
            'paid_by' => ['required', 'string', Rule::in(['owner', 'tenant'])],
            'responsible_party' => ['required', 'string', Rule::in(['owner', 'tenant'])],
            'settlement_type' => ['required', 'string', Rule::in(['none', 'deduct_from_rent', 'deduct_from_utilities', 'reimburse'])],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $leaseId = $this->input('lease_id');
            $propertyId = $this->input('property_id');
            $paidBy = $this->input('paid_by');
            $responsibleParty = $this->input('responsible_party');
            $settlementType = $this->input('settlement_type');
            $expenseDate = $this->input('expense_date');

            if ($paidBy === 'tenant' && $responsibleParty === 'owner' && $settlementType === 'none') {
                $validator->errors()->add('settlement_type', 'Alege o decontare: scadere din chirie, scadere din utilitati sau rambursare.');
            }

            if ($paidBy === 'tenant' && $responsibleParty === 'tenant' && $settlementType !== 'none') {
                $validator->errors()->add('settlement_type', 'Cheltuielile platite si suportate de chirias nu se deconteaza.');
            }

            if ($paidBy === 'owner' && $responsibleParty === 'owner' && $settlementType !== 'none') {
                $validator->errors()->add('settlement_type', 'Cheltuielile platite si suportate de proprietar nu se deconteaza.');
            }

            if ($paidBy === 'owner' && $responsibleParty === 'tenant' && $settlementType !== 'reimburse') {
                $validator->errors()->add('settlement_type', 'Cheltuielile platite de proprietar, dar suportate de chirias, trebuie marcate ca rambursare separata.');
            }

            $team = $this->route('current_team');
            $teamId = $team instanceof Team ? $team->id : null;
            $lease = null;

            if ($leaseId && $propertyId && ! $validator->errors()->has('property_id')) {
                $lease = Lease::query()
                    ->where('team_id', $teamId)
                    ->whereKey($leaseId)
                    ->first();

                if ($lease && (int) $lease->property_id !== (int) $propertyId) {
                    $validator->errors()->add('lease_id', __('The selected lease does not belong to the selected property.'));
                }
            }

            if (
                ! $propertyId
                || ! $expenseDate
                || $validator->errors()->has('property_id')
                || $validator->errors()->has('expense_date')
            ) {
                return;
            }

            $expenseDate = Carbon::parse($expenseDate, config('app.timezone'))->toDateString();

            if ($lease) {
                if (
                    $lease->start_date->toDateString() > $expenseDate
                    || ($lease->end_date && $lease->end_date->toDateString() < $expenseDate)
                ) {
                    $validator->errors()->add('lease_id', 'Contractul selectat nu este activ la data cheltuielii.');
                }
            }

            if (
                ($paidBy === 'tenant' || $responsibleParty === 'tenant')
                && ! $this->activeLeaseExists($teamId, (int) $propertyId, $expenseDate)
            ) {
                $message = 'Nu există contract activ pentru această proprietate la data cheltuielii. Nu poți selecta chiriașul ca plătitor sau responsabil.';

                if ($paidBy === 'tenant') {
                    $validator->errors()->add('paid_by', $message);
                }

                if ($responsibleParty === 'tenant') {
                    $validator->errors()->add('responsible_party', $message);
                }
            }
        });
    }

    /**
     * Get the validated data with field defaults.
     *
     * @return array<string, mixed>
     */
    public function validatedWithDefaults(): array
    {
        $validated = array_merge(
            [
                'currency' => 'RON',
                'paid_by' => 'owner',
                'responsible_party' => 'owner',
                'settlement_type' => 'none',
            ],
            $this->validated(),
        );

        if (in_array($validated['category'], ['insurance', 'admin'], true)) {
            $validated['category'] = 'other';
        }

        $validated['status'] = $this->derivedStatus(
            $validated['paid_by'],
            $validated['responsible_party'],
            $validated['settlement_type'],
        );

        return $validated;
    }

    private function activeLeaseExists(?int $teamId, int $propertyId, string $expenseDate): bool
    {
        return Lease::query()
            ->where('team_id', $teamId)
            ->where('property_id', $propertyId)
            ->whereDate('start_date', '<=', $expenseDate)
            ->where(function ($query) use ($expenseDate) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $expenseDate);
            })
            ->exists();
    }

    private function derivedStatus(string $paidBy, string $responsibleParty, string $settlementType): string
    {
        if (
            ($paidBy === 'owner' && $responsibleParty === 'tenant')
            || ($paidBy === 'tenant' && $responsibleParty === 'owner' && $settlementType === 'reimburse')
        ) {
            return 'reimbursable';
        }

        return 'paid';
    }
}
