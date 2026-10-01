<?php

namespace App\Http\Requests\Utilities;

use App\Models\Team;
use App\Models\UtilityBill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AnalyzeUtilityBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('current_team');

        return $team instanceof Team
            && Gate::allows('create', [UtilityBill::class, $team]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attachment' => [
                'required',
                'file',
                'mimes:pdf',
                'max:20480',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachment.required' => __('validation.custom.utility_bill.reader.required'),
            'attachment.file' => __('validation.custom.utility_bill.reader.file'),
            'attachment.mimes' => __('validation.custom.utility_bill.reader.mimes'),
            'attachment.max' => __('validation.custom.utility_bill.reader.max'),
        ];
    }
}
