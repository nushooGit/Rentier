<?php

namespace App\Services;

use App\Models\Lease;
use Illuminate\Database\Eloquent\Collection;

class ApplicableExpenseLeaseResolver
{
    /**
     * @return Collection<int, Lease>
     */
    public function matches(?int $teamId, int $propertyId, string $expenseDate): Collection
    {
        return Lease::query()
            ->where('team_id', $teamId)
            ->where('property_id', $propertyId)
            ->whereDate('start_date', '<=', $expenseDate)
            ->where(function ($query) use ($expenseDate) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $expenseDate);
            })
            ->orderByDesc('start_date')
            ->get();
    }
}
