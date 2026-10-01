<?php

namespace App\Http\Requests\Reminders;

use App\Models\Reminder;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('current_team');

        return $team instanceof Team
            && Gate::allows('create', [Reminder::class, $team]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $team = $this->route('current_team');
        $teamId = $team instanceof Team ? $team->id : null;

        return [
            'title' => ['required', 'string', 'max:191'],
            'remind_on' => ['required', 'date'],
            'property_id' => [
                'nullable',
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
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
