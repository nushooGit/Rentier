<?php

namespace App\Models;

use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property string $type
 * @property string $country
 * @property string $city
 * @property string|null $county_or_sector
 * @property string $address_line
 * @property string|null $postal_code
 * @property int|null $rooms
 * @property string|null $usable_area_sqm
 * @property int|null $floor
 * @property int|null $total_floors
 * @property string $status
 * @property string|null $monthly_rent_amount
 * @property string $currency
 * @property int|null $rent_due_day
 * @property string|null $deposit_amount
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 */
#[Fillable([
    'team_id',
    'name',
    'type',
    'country',
    'city',
    'county_or_sector',
    'address_line',
    'postal_code',
    'rooms',
    'usable_area_sqm',
    'floor',
    'total_floors',
    'status',
    'monthly_rent_amount',
    'currency',
    'rent_due_day',
    'deposit_amount',
    'notes',
])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    /**
     * Get the owning workspace.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rooms' => 'integer',
            'floor' => 'integer',
            'total_floors' => 'integer',
            'rent_due_day' => 'integer',
            'usable_area_sqm' => 'decimal:2',
            'monthly_rent_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
        ];
    }
}
