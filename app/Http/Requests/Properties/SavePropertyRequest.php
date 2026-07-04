<?php

namespace App\Http\Requests\Properties;

use App\Models\Property;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SavePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $property = $this->route('property');

        if ($property instanceof Property) {
            return Gate::allows('update', $property);
        }

        $team = $this->route('current_team');

        return $team instanceof Team
            && Gate::allows('create', [Property::class, $team]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['studio', 'apartment', 'house', 'commercial_space', 'office', 'other'])],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:255'],
            'county_or_sector' => ['nullable', 'string', 'max:255'],
            'address_line' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'rooms' => ['nullable', 'integer', 'min:0'],
            'usable_area_sqm' => ['nullable', 'numeric', 'min:0'],
            'floor' => ['nullable', 'integer'],
            'total_floors' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', Rule::in(['available', 'occupied', 'renovation', 'inactive'])],
            'monthly_rent_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
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
                'country' => 'Romania',
                'currency' => 'RON',
            ],
            $this->validated(),
        );
    }
}
