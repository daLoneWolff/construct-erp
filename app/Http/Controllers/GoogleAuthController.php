<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Responses\PostAuthResponse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Send the visitor to Google's consent screen.
     */
    public function redirect(): Response
    {
        if (! filled(config('services.google.client_id')) || ! filled(config('services.google.client_secret'))) {
            return redirect()->route('login')->with(
                'error',
                __('Google sign-in is not configured on this installation. Please use your email and password or contact your administrator.')
            );
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Sign in (or create) the user behind a verified Google email.
     */
    public function callback(): Response
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->with(
                'error',
                __('Google sign-in was not completed. Please try again or use your email and password.')
            );
        }

        $email = $googleUser->getEmail();
        $emailVerified = false;

        if ($googleUser instanceof SocialiteUser) {
            $raw = $googleUser->getRaw();
            $emailVerified = $raw['email_verified'] ?? $raw['verified_email'] ?? false;
        }

        if (! is_string($email) || $email === '' || ! $emailVerified) {
            return redirect()->route('login')->with(
                'error',
                __('Google did not provide a verified email address for this account.')
            );
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'email_verified_at' => now(),
                'password' => null,
                'role' => Role::Admin,
            ]);
        } elseif ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user, true);

        return app(PostAuthResponse::class)->toResponse(request());
    }
}
