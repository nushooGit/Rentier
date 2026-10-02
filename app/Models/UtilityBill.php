<?php

namespace App\Models;

use App\Enums\UtilityBillStatus;
use App\Enums\UtilityPaidBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $utility_account_id
 * @property int $property_id
 * @property int|null $lease_id
 * @property int|null $document_id
 * @property int|null $created_by_user_id
 * @property string $invoice_number
 * @property Carbon $billing_period_start
 * @property Carbon $billing_period_end
 * @property Carbon $issue_date
 * @property Carbon $due_date
 * @property int $amount_minor
 * @property string $currency
 * @property UtilityBillStatus $status
 * @property UtilityPaidBy|null $paid_by
 * @property Carbon|null $paid_on
 * @property string|null $notes
 * @property-read Team $team
 * @property-read UtilityAccount $utilityAccount
 * @property-read Property $property
 * @property-read Lease|null $lease
 * @property-read Document|null $document
 * @property-read Expense|null $expense
 */
#[Fillable([
    'team_id',
    'utility_account_id',
    'property_id',
    'lease_id',
    'document_id',
    'created_by_user_id',
    'invoice_number',
    'billing_period_start',
    'billing_period_end',
    'issue_date',
    'due_date',
    'amount_minor',
    'currency',
    'status',
    'paid_by',
    'paid_on',
    'notes',
])]
class UtilityBill extends Model
{
    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<UtilityAccount, $this> */
    public function utilityAccount(): BelongsTo
    {
        return $this->belongsTo(UtilityAccount::class);
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

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** @return HasOne<Expense, $this> */
    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }

    protected function casts(): array
    {
        return [
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'amount_minor' => 'integer',
            'status' => UtilityBillStatus::class,
            'paid_by' => UtilityPaidBy::class,
            'paid_on' => 'date',
        ];
    }
}
