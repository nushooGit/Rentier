<?php

namespace App\Http\Requests\Expenses;

use App\Models\Expense;
use App\Models\Lease;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
            'category' => ['required', 'string', Rule::in(['maintenance', 'utilities', 'taxes', 'insurance', 'admin', 'repairs', 'other'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'expense_date' => ['required', 'date'],
            'paid_by' => ['required', 'string', Rule::in(['landlord', 'renter', 'other'])],
            'status' => ['required', 'string', Rule::in(['paid', 'pending', 'reimbursable', 'cancelled'])],
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

            if (! $leaseId || ! $propertyId) {
                return;
            }

            $leasePropertyId = Lease::query()
                ->whereKey($leaseId)
                ->value('property_id');

            if ($leasePropertyId && (int) $leasePropertyId !== (int) $propertyId) {
                $validator->errors()->add('lease_id', __('The selected lease does not belong to the selected property.'));
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
        return array_merge(
            [
                'currency' => 'RON',
            ],
            $this->validated(),
        );
    }
}
