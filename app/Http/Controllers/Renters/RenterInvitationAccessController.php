<?php

namespace App\Http\Controllers\Renters;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Renters\ManageRenterInvitation;
use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RenterInvitationAccessController extends Controller
{
    use PasswordValidationRules;

    public function show(Request $request, string $token, ManageRenterInvitation $action): Response
    {
        $invitation = $action->pending($token);

        return Inertia::render('renter-invitations/show', [
            'token' => $token,
            'email' => $invitation->email,
            'authenticated' => $request->user() !== null,
            'verified' => $request->user()?->hasVerifiedEmail() ?? false,
            'matchesEmail' => $request->user() !== null
                && strcasecmp($request->user()->email, $invitation->email) === 0,
        ]);
    }

    public function register(Request $request, string $token, ManageRenterInvitation $action, CreateNewUser $createNewUser): RedirectResponse
    {
        abort_if($request->user() !== null, 403);

        $key = 'renter-enroll:'.sha1($request->ip().'|'.$token);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429);
        }
        RateLimiter::hit($key, 3600);

        $invitation = $action->pending($token);
        $input = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => $this->passwordRules(),
            'password_confirmation' => ['required', 'string'],
        ]);

        if (strcasecmp($input['email'], $invitation->email) !== 0) {
            throw ValidationException::withMessages(['email' => __('This invitation was sent to a different email address.')]);
        }

        $user = $createNewUser->create($input);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('renter-invitations.show', ['token' => $token]);
    }
}
