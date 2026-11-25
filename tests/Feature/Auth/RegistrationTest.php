<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_register_page_places_google_button_after_the_register_button(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        $registerButton = strpos($html, 'data-test="register-button"');
        $googleButton = strpos($html, 'Continue with Google');

        $this->assertNotFalse($registerButton);
        $this->assertNotFalse($googleButton);
        $this->assertLessThan($googleButton, $registerButton);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('verification.notice', absolute: false));

        $this->assertAuthenticated();
        $this->assertNull(auth()->user()->email_verified_at);
    }
}
