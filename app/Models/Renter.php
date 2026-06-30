<?php

namespace App\Models;

use Database\Factories\RenterFactory;
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
 * @property int|null $user_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Lease> $leases
 * @property-read Collection<int, RentPayment> $rentPayments
 * @property-read Team $team
 * @property-read User|null $user
 */
#[Fillable([
    'team_id',
    'user_id',
    'name',
    'email',
    'phone',
    'notes',
])]
class Renter extends Model
{
    /** @use HasFactory<RenterFactory> */
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
     * Get the linked renter portal user, if one exists.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get this renter's leases.
     *
     * @return HasMany<Lease, $this>
     */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    /**
     * Get rent payments for this renter.
     *
     * @return HasMany<RentPayment, $this>
     */
    public function rentPayments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }
}
