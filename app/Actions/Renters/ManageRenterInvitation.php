<?php

namespace App\Actions\Renters;

use App\Models\Renter;
use App\Models\RenterInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageRenterInvitation
{
    /**
     * Issue an opaque, one-time invitation for an explicitly selected renter contact.
     * The raw token is returned to the caller once and is never stored in the database.
     *
     * @return array{invitation: RenterInvitation, token: string}
     */
    public function issue(User $actor, Renter $renter): array
    {
        abort_unless($actor->ownsTeam($renter->team), 403);
        abort_if($renter->team->isSuspended(), 423);
        abort_if($renter->user_id !== null, 422);
        abort_if($renter->email === null || trim($renter->email) === '', 422);

        return DB::transaction(function () use ($actor, $renter): array {
            $locked = Renter::query()->lockForUpdate()->findOrFail($renter->id);
            abort_if($locked->user_id !== null, 422);

            RenterInvitation::query()
                ->where('renter_id', $locked->id)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $token = Str::random(64);
            $invitation = RenterInvitation::create([
                'team_id' => $locked->team_id,
                'renter_id' => $locked->id,
                'invited_by' => $actor->id,
                'email' => $locked->email,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(3),
            ]);

            return ['invitation' => $invitation, 'token' => $token];
        });
    }

    public function pending(string $rawToken): RenterInvitation
    {
        $invitation = RenterInvitation::query()
            ->where('token_hash', hash('sha256', $rawToken))
            ->first();

        if (! $invitation
            || $invitation->accepted_at !== null
            || $invitation->revoked_at !== null
            || $invitation->expires_at->isPast()
            || $invitation->team->isSuspended()
            || $invitation->renter->team_id !== $invitation->team_id
            || $invitation->renter->user_id !== null
            || strcasecmp((string) $invitation->renter->email, $invitation->email) !== 0) {
            throw ValidationException::withMessages(['invitation' => __('Invalid or expired invitation.')]);
        }

        return $invitation;
    }

    public function accept(User $user, string $rawToken): Renter
    {
        abort_unless($user->hasVerifiedEmail(), 403);
        abort_if($user->isSuspended(), 423);

        return DB::transaction(function () use ($user, $rawToken): Renter {
            $invitation = RenterInvitation::query()
                ->where('token_hash', hash('sha256', $rawToken))
                ->lockForUpdate()
                ->first();

            if (! $invitation
                || $invitation->accepted_at !== null
                || $invitation->revoked_at !== null
                || $invitation->expires_at->isPast()
                || strcasecmp($user->email, $invitation->email) !== 0
                || $invitation->team->isSuspended()) {
                throw ValidationException::withMessages(['invitation' => __('Invalid or expired invitation.')]);
            }

            $renter = Renter::query()->lockForUpdate()->findOrFail($invitation->renter_id);

            if ($renter->team_id !== $invitation->team_id
                || $renter->user_id !== null
                || strcasecmp((string) $renter->email, $invitation->email) !== 0) {
                throw ValidationException::withMessages(['invitation' => __('Invalid or expired invitation.')]);
            }

            $renter->update(['user_id' => $user->id]);
            $invitation->update(['accepted_at' => now()]);

            return $renter;
        });
    }

    public function revoke(User $actor, RenterInvitation $invitation): void
    {
        abort_unless($actor->ownsTeam($invitation->team), 403);
        abort_if($invitation->accepted_at !== null, 422);

        $invitation->update(['revoked_at' => now()]);
    }
}
