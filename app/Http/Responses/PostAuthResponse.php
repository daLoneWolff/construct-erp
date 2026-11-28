<?php

namespace App\Http\Responses;

use App\Enums\Role;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Support\Responsable;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PostAuthResponse implements LoginResponseContract, RegisterResponseContract, Responsable, VerifyEmailResponseContract
{
    /**
     * Send every successful auth path through the same onboarding decision.
     */
    public function toResponse($request): Response
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user?->role === Role::Contractor) {
            $destination = $user->current_company_id !== null
                ? route('portal.dashboard', absolute: false)
                : route('companies.create', absolute: false);
        } else {
            $destination = $user?->current_company_id !== null
                ? route('dashboard', absolute: false)
                : route('companies.create', absolute: false);
        }

        if ($request->routeIs('verification.verify')) {
            $destination .= '?verified=1';
        }

        if ($request->wantsJson()) {
            return response()->json(['redirect' => $destination]);
        }

        return redirect()->intended($destination);
    }
}
