<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $property_id
 * @property int|null $lease_id
 * @property string $title
 * @property string $category
 * @property string $amount
 * @property string $currency
 * @property Carbon $expense_date
 * @property string $paid_by
 * @property string $responsible_party
 * @property string $settlement_type
 * @property Carbon|null $settled_at
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Property $property
 * @property-read Lease|null $lease
 */
#[Fillable([
    'team_id',
    'property_id',
    'lease_id',
    'title',
    'category',
    'amount',
    'currency',
    'expense_date',
    'paid_by',
    'responsible_party',
    'settlement_type',
    'settled_at',
    'status',
    'notes',
])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'category' => 'other',
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
    ];

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
     * Get the related property.
     *
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the related lease, if any.
     *
     * @return BelongsTo<Lease, $this>
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function requiresOwnerReimbursement(): bool
    {
        return $this->paid_by === 'tenant'
            && $this->responsible_party === 'owner'
            && $this->settlement_type === 'reimburse';
    }

    public function requiresTenantRecovery(): bool
    {
        return $this->paid_by === 'owner'
            && $this->responsible_party === 'tenant'
            && $this->settlement_type === 'reimburse';
    }

    public function isSettlementOpen(): bool
    {
        return $this->settled_at === null
            && ($this->requiresOwnerReimbursement() || $this->requiresTenantRecovery());
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeActiveForDashboard(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled');
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeOwnerReimbursements(Builder $query): Builder
    {
        return $query
            ->where('paid_by', 'tenant')
            ->where('responsible_party', 'owner')
            ->where('settlement_type', 'reimburse');
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeTenantRecoveries(Builder $query): Builder
    {
        return $query
            ->where('paid_by', 'owner')
            ->where('responsible_party', 'tenant')
            ->where('settlement_type', 'reimburse');
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeUnsettledSettlement(Builder $query): Builder
    {
        return $query->whereNull('settled_at');
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeSettledInMonth(Builder $query, int $year, int $month): Builder
    {
        return $query
            ->whereNotNull('settled_at')
            ->whereYear('settled_at', $year)
            ->whereMonth('settled_at', $month);
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
            'expense_date' => 'date',
            'settled_at' => 'datetime',
        ];
    }
}
