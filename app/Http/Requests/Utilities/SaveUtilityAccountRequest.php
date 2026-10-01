<?php

namespace App\Http\Requests\Utilities;

use App\Enums\UtilityAccountStatus;
use App\Enums\UtilityResponsibleParty;
use App\Enums\UtilityServiceType;
use App\Models\Lease;
use App\Models\Team;
use App\Models\UtilityAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveUtilityAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('current_team');
        $utilityAccount = $this->route('utility_account');

        if (! $team instanceof Team) {
            return false;
        }

        if ($utilityAccount instanceof UtilityAccount) {
            return $utilityAccount->team_id === $team->id
                && Gate::allows('update', $utilityAccount);
        }

        return Gate::allows('create', [UtilityAccount::class, $team]);
    }

    /**
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
                Rule::exists('properties', 'id')
                    ->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'lease_id' => [
                'nullable',
                'integer',
                Rule::exists('leases', 'id')
                    ->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'provider_name' => ['required', 'string', 'max:191'],
            'service_type' => ['required', Rule::enum(UtilityServiceType::class)],
            'account_identifier' => ['nullable', 'string', 'max:191'],
            'responsible_party' => ['required', Rule::enum(UtilityResponsibleParty::class)],
            'status' => ['required', Rule::enum(UtilityAccountStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                $validator->errors()->has('property_id')
                || $validator->errors()->has('lease_id')
            ) {
                return;
            }

            $team = $this->route('current_team');

            if (! $team instanceof Team) {
                return;
            }

            if ($this->input('responsible_party') === UtilityResponsibleParty::Renter->value
                && ! $this->filled('lease_id')) {
                $validator->errors()->add(
                    'lease_id',
                    __('validation.custom.utility_account.lease_required_for_renter'),
                );

                return;
            }

            if (! $this->filled('lease_id')) {
                return;
            }

            $lease = Lease::query()
                ->whereBelongsTo($team)
                ->whereKey($this->integer('lease_id'))
                ->first();

            if ($lease && $lease->property_id !== $this->integer('property_id')) {
                $validator->errors()->add(
                    'lease_id',
                    __('validation.custom.utility_account.lease_property_mismatch'),
                );
            }
        });
    }
}
