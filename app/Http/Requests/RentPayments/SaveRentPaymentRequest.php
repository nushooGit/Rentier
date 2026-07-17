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
        $rentPayment = $this->rentPaymentFromRoute();

        if ($rentPayment !== null) {
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
            'payment_type' => ['required', 'string', Rule::in(['rent', 'guarantee'])],
            'payment_date' => ['required', 'date'],
            'period_month' => ['nullable', 'required_if:payment_type,rent', 'integer', 'between:1,12'],
            'period_year' => ['nullable', 'required_if:payment_type,rent', 'integer', 'between:2000,2100'],
            'method' => ['nullable', 'string', Rule::in(['cash', 'bank_transfer', 'card', 'other'])],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || $this->input('payment_type') !== 'guarantee') {
                return;
            }

            $team = $this->route('current_team');
            $teamId = $team instanceof Team ? $team->id : null;
            $lease = Lease::query()
                ->where('team_id', $teamId)
                ->find($this->integer('lease_id'));

            if (! $lease) {
                return;
            }

            $expectedGuarantee = (float) ($lease->deposit_amount ?? 0);

            if ($expectedGuarantee <= 0) {
                $validator->errors()->add('amount', 'Acest contract nu are garanție de încasat.');

                return;
            }

            $payment = $this->rentPaymentFromRoute();
            $existingTotalQuery = RentPayment::query()
                ->where('lease_id', $lease->id)
                ->where('payment_type', 'guarantee');

            if ($payment !== null) {
                $existingTotalQuery->whereKeyNot($payment->id);
            }

            $existingTotal = (float) $existingTotalQuery->sum('amount');
            $newTotalCents = $this->moneyToCents($existingTotal) + $this->moneyToCents((float) $this->input('amount'));
            $expectedGuaranteeCents = $this->moneyToCents($expectedGuarantee);

            if ($newTotalCents > $expectedGuaranteeCents) {
                $validator->errors()->add('amount', 'Suma garanției depășește garanția stabilită în contract.');
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
                'payment_type' => 'rent',
            ],
            $this->validated(),
        );
    }

    private function moneyToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function rentPaymentFromRoute(): ?RentPayment
    {
        $rentPayment = $this->route('payment');

        return $rentPayment instanceof RentPayment ? $rentPayment : null;
    }
}
