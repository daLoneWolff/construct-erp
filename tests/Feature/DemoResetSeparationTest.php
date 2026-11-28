<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Company;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Models\User;
use App\Services\CompanySetupService;
use App\Services\TenantService;
use Database\Seeders\DemoResetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoResetSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_reset_removes_only_demo_owned_data_and_preserves_registered_workspaces(): void
    {
        $this->seed(DemoResetSeeder::class);

        $oldDemoCompany = Company::where('is_demo', true)->sole();
        $oldDemoPayment = DB::table('contractor_payments')
            ->where('company_id', $oldDemoCompany->id)
            ->orderBy('id')
            ->firstOrFail();

        DB::table('contractor_payments')
            ->where('id', $oldDemoPayment->id)
            ->update(['amount_paisa' => 1]);

        $fixture = $this->registeredWorkspace();

        $this->seed(DemoResetSeeder::class);

        $newDemoCompany = Company::where('is_demo', true)->sole();
        $newDemoPayment = DB::table('contractor_payments')
            ->where('company_id', $newDemoCompany->id)
            ->where('payment_no', $oldDemoPayment->payment_no)
            ->firstOrFail();

        $this->assertSame($oldDemoPayment->amount_paisa, $newDemoPayment->amount_paisa);
        $this->assertFalse(DB::table('contractor_payments')->where('id', $oldDemoPayment->id)->exists());
        $this->assertSame('Private Builders Pvt. Ltd.', Company::find($fixture['company']->id)?->name);
        $this->assertSame($fixture['user']->email, User::find($fixture['user']->id)?->email);
        $this->assertSame('PRJ-T5-001', Project::find($fixture['project']->id)?->code);
        $this->assertSame(1250000, ContractorPayment::find($fixture['payment']->id)?->amount_paisa);
        $this->assertSame(1, ContractorPayment::where('company_id', $fixture['company']->id)->count());
        $this->assertSame(1, $this->companyUserCountFor($fixture['company']->id));
        $this->assertSame(1, User::where('email', 'admin@ranta.test')->count());
        $this->assertSame(1, Company::where('is_demo', true)->count());
    }

    public function test_in_place_demo_reset_refreshes_the_authenticated_demo_user_session(): void
    {
        $this->seed(DemoResetSeeder::class);

        $this->registeredWorkspace();

        $demoUser = User::where('email', 'admin@ranta.test')->sole();

        config(['demo.reset_enabled' => true]);

        $this->actingAs($demoUser)
            ->post(route('demo.reset'))
            ->assertRedirect(route('dashboard'));

        $resetUser = User::where('email', 'admin@ranta.test')->sole();

        $this->assertNotSame($demoUser->id, $resetUser->id);
        $this->assertSame($resetUser->id, auth()->id());

        $this->actingAs($resetUser)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_demo_reset_is_forbidden_when_the_demo_config_flag_is_disabled(): void
    {
        $this->seed(DemoResetSeeder::class);

        config(['demo.reset_enabled' => false]);

        $this->actingAs(User::where('email', 'admin@ranta.test')->sole())
            ->post(route('demo.reset'))
            ->assertForbidden();
    }

    /**
     * @return array{company: Company, user: User, project: Project, payment: ContractorPayment}
     */
    private function registeredWorkspace(): array
    {
        $user = User::factory()->create();

        $company = app(CompanySetupService::class)->createForUser($user, [
            'name' => 'Private Builders Pvt. Ltd.',
            'pan' => '601234567',
            'vat' => '601234567',
            'address' => 'Ward 12, Lalitpur',
            'district' => 'Lalitpur',
            'fiscal_year' => '2083/84',
        ]);

        app(TenantService::class)->setCurrentCompany($company);

        $project = Project::create([
            'company_id' => $company->id,
            'code' => 'PRJ-T5-001',
            'name' => 'Private Tower',
            'client_name' => 'Private Client',
            'contract_value_paisa' => 100000000,
            'location_ward' => '1',
            'location_municipality' => 'Lalitpur',
            'location_district' => 'Lalitpur',
            'start_date' => today()->subMonth()->toDateString(),
            'end_date' => today()->addYear()->toDateString(),
            'project_manager_id' => $user->id,
            'advance_paisa' => 0,
            'status' => ProjectStatus::Active,
        ]);

        $contractor = Contractor::create([
            'company_id' => $company->id,
            'name' => 'Private Contractor',
            'phone' => '9841000000',
            'address' => 'Lalitpur',
            'pan' => '999999999',
            'is_active' => true,
        ]);

        $job = ProjectJob::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'contractor_id' => $contractor->id,
            'title' => 'Private masonry',
            'agreed_amount_paisa' => 5000000,
            'status' => 'todo',
            'starts_on' => today()->subWeek()->toDateString(),
            'due_on' => today()->addWeek()->toDateString(),
        ]);

        $payment = ContractorPayment::create([
            'company_id' => $company->id,
            'payment_no' => 'CTP-'.today()->year.'-0001',
            'project_id' => $project->id,
            'project_job_id' => $job->id,
            'contractor_id' => $contractor->id,
            'amount_paisa' => 1250000,
            'paid_on' => today()->toDateString(),
            'method' => 'bank_transfer',
        ]);

        return compact('company', 'user', 'project', 'payment');
    }

    private function companyUserCountFor(int $companyId): int
    {
        return DB::table('company_user')->where('company_id', $companyId)->count();
    }
}
