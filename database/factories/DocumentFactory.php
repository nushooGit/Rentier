<?php

namespace Database\Factories;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Property;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'property_id' => fn (array $attributes) => Property::factory()->state([
                'team_id' => $attributes['team_id'],
            ]),
            'lease_id' => null,
            'uploaded_by_user_id' => null,
            'category' => DocumentCategory::Other,
            'document_date' => fake()->date(),
            'expires_on' => null,
            'disk' => 'local',
            'path' => 'documents/factory/'.fake()->uuid().'.pdf',
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ];
    }
}
