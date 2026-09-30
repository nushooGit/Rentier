<?php

namespace App\Http\Requests\Documents;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('current_team');

        return $team instanceof Team
            && Gate::allows('create', [Document::class, $team]);
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
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'document_date' => ['required', 'date'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:document_date'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx',
                'max:20480',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                $validator->errors()->has('property_id')
                || $validator->errors()->has('lease_id')
                || ! $this->filled('lease_id')
            ) {
                return;
            }

            $team = $this->route('current_team');

            if (! $team instanceof Team) {
                return;
            }

            $lease = Lease::query()
                ->whereBelongsTo($team)
                ->whereKey($this->integer('lease_id'))
                ->first();

            if ($lease && $lease->property_id !== $this->integer('property_id')) {
                $validator->errors()->add(
                    'lease_id',
                    'Contractul selectat nu aparține proprietății selectate.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'property_id.required' => 'Alege proprietatea.',
            'category.required' => 'Alege categoria documentului.',
            'document_date.required' => 'Data documentului este obligatorie.',
            'expires_on.after_or_equal' => 'Data expirării trebuie să fie egală sau ulterioară datei documentului.',
            'file.required' => 'Alege un fișier.',
            'file.mimes' => 'Fișierul trebuie să fie PDF, imagine JPG/PNG/WebP sau document Word.',
            'file.max' => 'Fișierul poate avea maximum 20 MB.',
        ];
    }
}
