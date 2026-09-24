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
     * Do not reveal account existence through unknown-user or per-account throttle responses.
     */
    public function toResponse($request)
    {
        if (in_array($this->status, [PasswordBroker::INVALID_USER, PasswordBroker::RESET_THROTTLED], true)) {
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
