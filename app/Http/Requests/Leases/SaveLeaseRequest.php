<?php

namespace App\Http\Requests\Leases;

use App\Models\Lease;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveLeaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $lease = $this->route('lease');

        if ($lease instanceof Lease) {
            return Gate::allows('update', $lease);
        }

        $team = $this->route('current_team');

        return $team instanceof Team
            && Gate::allows('create', [Lease::class, $team]);
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
            'renter_name' => ['required', 'string', 'max:255'],
            'renter_email' => ['nullable', 'email', 'max:255'],
            'renter_phone' => ['nullable', 'string', 'max:64'],
            'renter_notes' => ['nullable', 'string', 'max:10000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'monthly_rent_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'rent_due_day' => ['nullable', 'integer', 'between:1,31'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(['upcoming', 'active', 'ended', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
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
