<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserSessionInvalidator
{
    public function invalidate(User $user): void
    {
        $user->forceFill([
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') !== 'database') {
            return;
        }

        $connection = config('session.connection');
        $table = (string) config('session.table', 'sessions');

        DB::connection(is_string($connection) && $connection !== '' ? $connection : null)
            ->table($table)
            ->where('user_id', $user->id)
            ->delete();
    }
}
