<?php

namespace App\Models;

use App\Services\LeaseRentStatusCalculator;
use Carbon\CarbonInterface;
use Database\Factories\PropertyFactory;
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
 * @property-read Collection<int, Expense> $expenses
 * @property-read Collection<int, Lease> $leases
 * @property-read Collection<int, RentPayment> $rentPayments
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
     * Get the leases for this property.
     *
     * @return HasMany<Lease, $this>
     */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    /**
     * Get the rent payments for this property.
     *
     * @return HasMany<RentPayment, $this>
     */
    public function rentPayments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }

    /**
     * Get the expenses for this property.
     *
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Determine whether the property has an active lease for the given date.
     */
    public function isOccupied(?Carbon $date = null): bool
    {
        $date ??= today();

        return $this->leases()
            ->whereDate('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            })
            ->exists();
    }

    /**
     * Get the occupancy status derived from current lease dates.
     */
    public function occupancyStatus(?Carbon $date = null): string
    {
        return $this->isOccupied($date) ? 'occupied' : 'available';
    }

    /**
     * Get the current rent payment status for the active lease.
     *
     * @return array{key: string, label: string, days: int|null, due_date: string, expected_amount: string, collected_amount: string, remaining_amount: string}|null
     */
    public function currentRentPaymentStatus(?CarbonInterface $date = null): ?array
    {
        $date ??= today();
        $lease = $this->activeLease($date);

        if (! $lease) {
            return null;
        }

        return app(LeaseRentStatusCalculator::class)->forLease($lease, $date);
    }

    private function activeLease(CarbonInterface $date): ?Lease
    {
        return $this->leases()
            ->whereDate('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            })
            ->orderByDesc('start_date')
            ->first();
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
