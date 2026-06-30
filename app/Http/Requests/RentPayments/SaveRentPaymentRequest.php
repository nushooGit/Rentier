<?php

namespace App\Http\Requests\RentPayments;

use App\Models\Lease;
use App\Models\RentPayment;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveRentPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $rentPayment = $this->route('payment');

        if ($rentPayment instanceof RentPayment) {
            return Gate::allows('update', $rentPayment);
        }

        $team = $this->route('current_team');

        return $team instanceof Team
            && Gate::allows('create', [RentPayment::class, $team]);
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
            'lease_id' => [
                'required',
                'integer',
                Rule::exists('leases', 'id')->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_date' => ['required', 'date'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year' => ['required', 'integer', 'between:2000,2100'],
            'method' => ['nullable', 'string', Rule::in(['cash', 'bank_transfer', 'card', 'other'])],
            'status' => ['required', 'string', Rule::in(['paid', 'partial', 'pending', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('status') !== 'paid') {
                return;
            }

            $team = $this->route('current_team');

            if (! $team instanceof Team || ! is_numeric($this->input('amount'))) {
                return;
            }

            $lease = Lease::query()
                ->whereBelongsTo($team)
                ->whereKey($this->input('lease_id'))
                ->first();

            if (! $lease) {
                return;
            }

            if ((float) $this->input('amount') < (float) $lease->monthly_rent_amount) {
                $validator->errors()->add(
                    'amount',
                    'Suma introdusă este mai mică decât chiria lunară. Marchează plata ca parțială sau introdu suma completă.'
                );
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
