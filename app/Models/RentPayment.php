<?php

namespace App\Models;

use Database\Factories\RentPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $lease_id
 * @property int $property_id
 * @property int $renter_id
 * @property string $amount
 * @property string $currency
 * @property Carbon $payment_date
 * @property int $period_month
 * @property int $period_year
 * @property string|null $method
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Lease $lease
 * @property-read Property $property
 * @property-read Renter $renter
 */
#[Fillable([
    'team_id',
    'lease_id',
    'property_id',
    'renter_id',
    'amount',
    'currency',
    'payment_date',
    'period_month',
    'period_year',
    'method',
    'status',
    'notes',
])]
class RentPayment extends Model
{
    /** @use HasFactory<RentPaymentFactory> */
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
     * Get the related lease.
     *
     * @return BelongsTo<Lease, $this>
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Get the related property.
     *
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the related renter.
     *
     * @return BelongsTo<Renter, $this>
     */
    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'period_month' => 'integer',
            'period_year' => 'integer',
        ];
    }
}
