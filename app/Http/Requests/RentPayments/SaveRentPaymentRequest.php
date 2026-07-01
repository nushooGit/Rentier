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
            $team = $this->route('current_team');

            if (
                ! $team instanceof Team
                || ! $this->input('lease_id')
                || ! is_numeric($this->input('amount'))
                || $validator->errors()->has('lease_id')
                || $validator->errors()->has('amount')
                || $validator->errors()->has('status')
            ) {
                return;
            }

            $lease = Lease::query()
                ->whereBelongsTo($team)
                ->whereKey($this->input('lease_id'))
                ->first();

            if (! $lease) {
                return;
            }

            $amount = number_format((float) $this->input('amount'), 2, '.', '');
            $monthlyRent = number_format((float) $lease->monthly_rent_amount, 2, '.', '');
            $amountComparison = bccomp($amount, $monthlyRent, 2);

            if ($amountComparison === 1) {
                $validator->errors()->add(
                    'amount',
                    'Suma introdusă depășește chiria lunară. Plățile în avans vor fi gestionate într-un modul viitor.'
                );

                return;
            }

            if ($this->input('status') === 'paid' && $amountComparison !== 0) {
                $validator->errors()->add(
                    'amount',
                    'Pentru o plată achitată integral, suma trebuie să fie egală cu chiria lunară.'
                );
            }

            if ($this->input('status') === 'partial' && $amountComparison >= 0) {
                $validator->errors()->add(
                    'amount',
                    'O plată parțială trebuie să fie mai mică decât chiria lunară.'
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
