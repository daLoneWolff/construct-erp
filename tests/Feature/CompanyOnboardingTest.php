<?php

namespace Tests\Feature;

use App\Enums\CompanyRole;
use App\Livewire\Company\Create as CompanyCreate;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Models\User;
use App\Services\CompanySetupService;
use App\Support\Npr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_screen_requires_a_verified_account_without_a_company(): void
    {
        $verified = User::factory()->create();

        $this->actingAs($verified)
            ->get(route('companies.create'))
            ->assertOk()
            ->assertSee('Set up your company')
            ->assertSee('Create company workspace');

        $unverified = User::factory()->unverified()->create();

        $this->actingAs($unverified)
            ->get(route('companies.create'))
            ->assertRedirect(route('verification.notice'));

        $company = Company::create([
            'name' => 'Existing Builders',
            'pan' => '601234567',
            'address' => 'Lalitpur',
            'district' => 'Lalitpur',
            'fiscal_year' => '2083/84',
        ]);
        $verified->forceFill(['current_company_id' => $company->id])->save();

        $this->actingAs($verified)
            ->get(route('companies.create'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_company_creation_writes_owner_membership_and_active_company(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CompanyCreate::class)
            ->set([
                'name' => 'Kathmandu Builders Pvt. Ltd.',
                'pan' => '601234567',
                'vat' => '601234567',
                'address' => 'Ward 12, Lalitpur',
                'district' => 'Lalitpur',
                'fiscal_year' => '2083/84',
            ])
            ->call('createCompany')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Your company workspace is ready.');

        $user->refresh();
        $company = Company::sole();
        $membership = CompanyUser::sole();

        $this->assertSame($company->id, $user->current_company_id);
        $this->assertSame($user->id, $company->owner_user_id);
        $this->assertSame('kathmandu-builders-pvt-ltd', $company->slug);
        $this->assertFalse($company->is_demo);
        $this->assertNotNull($company->onboarding_completed_at);
        $this->assertSame($company->id, $membership->company_id);
        $this->assertSame($user->id, $membership->user_id);
        $this->assertSame(CompanyRole::Owner, $membership->role);
    }

    public function test_a_second_company_cannot_be_created_for_the_same_account(): void
    {
        $user = User::factory()->create();
        $input = $this->companyInput('First Builders');

        app(CompanySetupService::class)->createForUser($user, $input);

        try {
            app(CompanySetupService::class)->createForUser($user, $this->companyInput('Second Builders'));
            $this->fail('The second company should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('name', $exception->errors());
        }

        $this->assertSame(1, Company::count());
        $this->assertSame(1, CompanyUser::count());
    }

    public function test_new_company_dashboard_is_empty_and_shows_placeholder_calls_to_action(): void
    {
        $user = User::factory()->create();
        $company = app(CompanySetupService::class)->createForUser($user, $this->companyInput('Empty Builders'));

        $this->assertSame(0, Project::where('company_id', $company->id)->count());
        $this->assertSame(0, Contractor::where('company_id', $company->id)->count());
        $this->assertSame(0, ProjectJob::where('company_id', $company->id)->count());
        $this->assertSame(0, ContractorPayment::where('company_id', $company->id)->count());

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Your workspace is ready')
            ->assertSee('Create your first project')
            ->assertSee('Add your first contractor')
            ->assertSee('Record your first payment')
            ->assertSee(Npr::format(0), false)
            ->assertDontSee('Reset demo');
    }

    public function test_demo_reset_is_forbidden_for_a_registered_company(): void
    {
        $user = User::factory()->create();
        app(CompanySetupService::class)->createForUser($user, $this->companyInput('Private Builders'));

        $this->actingAs($user)
            ->post(route('demo.reset'))
            ->assertForbidden();
    }

    /**
     * @return array{name: string, pan: string, vat: string, address: string, district: string, fiscal_year: string}
     */
    private function companyInput(string $name): array
    {
        return [
            'name' => $name,
            'pan' => '601234567',
            'vat' => '601234567',
            'address' => 'Ward 12, Lalitpur',
            'district' => 'Lalitpur',
            'fiscal_year' => '2083/84',
        ];
    }
}
