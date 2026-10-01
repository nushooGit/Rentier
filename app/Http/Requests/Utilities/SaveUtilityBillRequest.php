<?php

namespace App\Http\Requests\Utilities;

use App\Enums\UtilityBillStatus;
use App\Models\Team;
use App\Models\UtilityAccount;
use App\Models\UtilityBill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveUtilityBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('current_team');
        $utilityBill = $this->route('utility_bill');

        if (! $team instanceof Team) {
            return false;
        }

        if ($utilityBill instanceof UtilityBill) {
            return $utilityBill->team_id === $team->id
                && Gate::allows('update', $utilityBill);
        }

        return Gate::allows('create', [UtilityBill::class, $team]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $team = $this->route('current_team');
        $teamId = $team instanceof Team ? $team->id : null;
        $utilityBill = $this->route('utility_bill');

        return [
            'utility_account_id' => [
                'required',
                'integer',
                Rule::exists('utility_accounts', 'id')
                    ->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'invoice_number' => [
                'required',
                'string',
                'max:191',
                Rule::unique('utility_bills', 'invoice_number')
                    ->where(fn ($query) => $query->where(
                        'utility_account_id',
                        $this->integer('utility_account_id'),
                    ))
                    ->ignore($utilityBill instanceof UtilityBill ? $utilityBill->id : null),
            ],
            'billing_period_start' => ['required', 'date'],
            'billing_period_end' => ['required', 'date', 'after_or_equal:billing_period_start'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'amount' => ['required', 'regex:/^\d{1,12}(?:[\.,]\d{1,2})?$/'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'status' => ['required', Rule::enum(UtilityBillStatus::class)],
            'paid_on' => [
                'nullable',
                'date',
                'required_if:status,'.UtilityBillStatus::Paid->value,
                'after_or_equal:issue_date',
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:20480',
            ],
        ];
    }
}
