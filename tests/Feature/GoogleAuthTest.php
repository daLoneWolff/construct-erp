<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_screens_offer_google_sign_in(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee(route('auth.google.redirect'), false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee(route('auth.google.redirect'), false);
    }

    public function test_google_redirect_shows_a_friendly_error_when_credentials_are_missing(): void
    {
        config()->set('services.google.client_id', null);
        config()->set('services.google.client_secret', null);

        Socialite::shouldReceive('driver')->never();

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHas(
                'error',
                'Google sign-in is not configured on this installation. Please use your email and password or contact your administrator.'
            );
    }

    public function test_google_redirect_requires_both_credentials(): void
    {
        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', null);

        Socialite::shouldReceive('driver')->never();

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_google_redirect_delegates_to_socialite_when_credentials_are_configured(): void
    {
        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-client-secret');

        $googleUrl = 'https://accounts.google.com/o/oauth2/v2/auth?client_id=test-client-id';

        $provider = Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('redirect')->once()->andReturn(redirect($googleUrl));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect($googleUrl)
            ->assertSessionHasNoErrors();
    }

    public function test_google_callback_creates_a_verified_passwordless_user_and_starts_onboarding(): void
    {
        $this->mockGoogleUser([
            'name' => 'Google Owner',
            'email' => 'google-owner@example.com',
            'email_verified' => true,
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('companies.create', absolute: false));

        $user = User::query()->where('email', 'google-owner@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Google Owner', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);
        $this->assertNull($user->current_company_id);
    }

    public function test_returning_google_user_reuses_the_same_account_and_workspace(): void
    {
        $company = Company::create([
            'name' => 'Returning Builders',
            'pan' => '601234567',
            'address' => 'Lalitpur',
            'district' => 'Lalitpur',
            'fiscal_year' => '2083/84',
        ]);

        $user = User::factory()->create([
            'name' => 'Returning Google User',
            'email' => 'returning-google@example.com',
            'current_company_id' => $company->id,
        ]);

        $this->mockGoogleUser([
            'name' => 'Different Google Name',
            'email' => $user->email,
            'email_verified' => true,
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame(1, User::query()->where('email', $user->email)->count());
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Returning Google User', $user->fresh()->name);
    }

    public function test_failed_google_consent_returns_to_login_with_an_error(): void
    {
        $provider = Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('user')->once()->andThrow(new RuntimeException('consent denied'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_unverified_google_email_is_rejected_without_creating_an_account(): void
    {
        $this->mockGoogleUser([
            'name' => 'Unverified Google User',
            'email' => 'unverified-google@example.com',
            'email_verified' => false,
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function mockGoogleUser(array $attributes): void
    {
        $provider = Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('user')->once()->andReturn(SocialiteUser::fake($attributes));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }
}
