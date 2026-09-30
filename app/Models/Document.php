<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $property_id
 * @property int|null $lease_id
 * @property int|null $uploaded_by_user_id
 * @property DocumentCategory $category
 * @property Carbon $document_date
 * @property Carbon|null $expires_on
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Property|null $property
 * @property-read Lease|null $lease
 * @property-read User|null $uploadedBy
 */
#[Fillable([
    'team_id',
    'property_id',
    'lease_id',
    'uploaded_by_user_id',
    'category',
    'document_date',
    'expires_on',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size_bytes',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<Lease, $this>
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'document_date' => 'date',
            'expires_on' => 'date',
            'size_bytes' => 'integer',
        ];
    }
}
