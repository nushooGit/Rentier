<?php

namespace App\Http\Responses;

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;

class FailedPasswordResetLinkRequestResponse implements FailedPasswordResetLinkRequestResponseContract
{
    public function __construct(private readonly string $status) {}

    /**
     * Do not reveal whether an account exists for a submitted email address.
     */
    public function toResponse($request)
    {
        if ($this->status === PasswordBroker::INVALID_USER) {
            $message = trans('passwords.sent');

            return $request->wantsJson()
                ? new JsonResponse(['message' => $message])
                : back()->with('status', $message);
        }

        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'email' => [trans($this->status)],
            ]);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => trans($this->status)]);
    }
}
