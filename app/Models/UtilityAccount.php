<?php

namespace App\Models;

use App\Enums\UtilityAccountStatus;
use App\Enums\UtilityResponsibleParty;
use App\Enums\UtilityServiceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $team_id
 * @property int $property_id
 * @property int|null $lease_id
 * @property string $provider_name
 * @property UtilityServiceType $service_type
 * @property string|null $account_identifier
 * @property UtilityResponsibleParty $responsible_party
 * @property UtilityAccountStatus $status
 * @property string|null $notes
 * @property int $bills_count
 * @property-read Team $team
 * @property-read Property $property
 * @property-read Lease|null $lease
 */
#[Fillable([
    'team_id',
    'property_id',
    'lease_id',
    'provider_name',
    'service_type',
    'account_identifier',
    'responsible_party',
    'status',
    'notes',
])]
class UtilityAccount extends Model
{
    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** @return BelongsTo<Lease, $this> */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /** @return HasMany<UtilityBill, $this> */
    public function bills(): HasMany
    {
        return $this->hasMany(UtilityBill::class);
    }

    protected function casts(): array
    {
        return [
            'service_type' => UtilityServiceType::class,
            'responsible_party' => UtilityResponsibleParty::class,
            'status' => UtilityAccountStatus::class,
        ];
    }
}
