<?php

namespace App\Models;

use Database\Factories\LeaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $property_id
 * @property int $renter_id
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property string $monthly_rent_amount
 * @property string $currency
 * @property int|null $rent_due_day
 * @property string|null $deposit_amount
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Property $property
 * @property-read Renter $renter
 * @property-read Collection<int, RentPayment> $rentPayments
 * @property-read Collection<int, Expense> $expenses
 */
#[Fillable([
    'team_id',
    'property_id',
    'renter_id',
    'start_date',
    'end_date',
    'monthly_rent_amount',
    'currency',
    'rent_due_day',
    'deposit_amount',
    'status',
    'notes',
])]
class Lease extends Model
{
    /** @use HasFactory<LeaseFactory> */
    use HasFactory;

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

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
     * Get the leased property.
     *
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the renter contact for this lease.
     *
     * @return BelongsTo<Renter, $this>
     */
    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class);
    }

    /**
     * Get the rent payments for this lease.
     *
     * @return HasMany<RentPayment, $this>
     */
    public function rentPayments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }

    /**
     * Get the expenses linked to this lease.
     *
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the lease status derived from its date interval.
     */
    public function computedStatus(?Carbon $date = null): string
    {
        return self::computeStatusForDates($this->start_date, $this->end_date, $date);
    }

    /**
     * Compute a lease status from its start and end dates.
     */
    public static function computeStatusForDates(mixed $startDate, mixed $endDate = null, ?Carbon $date = null): string
    {
        $date ??= today();
        $startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDate = $endDate instanceof Carbon || $endDate === null ? $endDate : Carbon::parse($endDate);

        if ($startDate->isAfter($date)) {
            return self::STATUS_UPCOMING;
        }

        if ($endDate !== null && $endDate->isBefore($date)) {
            return self::STATUS_ENDED;
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'rent_due_day' => 'integer',
            'monthly_rent_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
        ];
    }
}
