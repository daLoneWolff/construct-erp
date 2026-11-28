<?php

use App\Enums\CompanyRole;
use App\Enums\ProjectStatus;
use App\Livewire\Company\Create as CompanyCreate;
use App\Livewire\Projects\Show as ProjectShow;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Models\User;
use App\Services\CalculationService;
use App\Services\CompanySetupService;
use App\Services\TenantService;
use App\Support\Npr;
use Database\Seeders\DemoResetSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;

/** @return array{company: Company, user: User, project: Project, contractor: Contractor, job: ProjectJob, payment: ContractorPayment} */
function t6Workspace(string $label, string $pan): array
{
    $company = Company::create([
        'name' => $label.' Acceptance Builders',
        'slug' => strtolower(str_replace(' ', '-', $label)).'-acceptance-'.$pan,
        'pan' => $pan,
        'vat' => $pan,
        'address' => 'Ward 12, Lalitpur',
        'district' => 'Lalitpur',
        'fiscal_year' => '2083/84',
    ]);

    $user = User::factory()->create([
        'name' => $label.' Acceptance Owner',
        'email' => strtolower($label).'-owner@acceptance.test',
        'email_verified_at' => now(),
    ]);

    $company->forceFill(['owner_user_id' => $user->id])->save();

    CompanyUser::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'role' => CompanyRole::Owner,
        'joined_at' => now(),
    ]);

    $user->forceFill(['current_company_id' => $company->id])->save();

    app(TenantService::class)->setCurrentCompany($company);

    $project = Project::create([
        'company_id' => $company->id,
        'code' => 'PRJ-'.strtoupper(substr($label, 0, 3)).'-T6',
        'name' => $label.' Acceptance Project',
        'client_name' => $label.' Acceptance Client',
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
        'name' => $label.' Acceptance Contractor',
        'phone' => '9841000000',
        'address' => 'Lalitpur',
        'pan' => $pan,
        'is_active' => true,
    ]);

    $job = ProjectJob::create([
        'company_id' => $company->id,
        'project_id' => $project->id,
        'contractor_id' => $contractor->id,
        'title' => $label.' Acceptance Job',
        'agreed_amount_paisa' => 10000000,
        'status' => 'todo',
        'starts_on' => today()->subWeek()->toDateString(),
        'due_on' => today()->addWeek()->toDateString(),
    ]);

    $payment = ContractorPayment::create([
        'company_id' => $company->id,
        'payment_no' => 'CTP-'.today()->year.'-'.($label === 'Alpha' ? '0001' : '0009'),
        'project_id' => $project->id,
        'project_job_id' => $job->id,
        'contractor_id' => $contractor->id,
        'amount_paisa' => 2500000,
        'paid_on' => today()->toDateString(),
        'method' => 'bank_transfer',
    ]);

    return compact('company', 'user', 'project', 'contractor', 'job', 'payment');
}

/** @return array{name: string, pan: string, vat: string, address: string, district: string, fiscal_year: string} */
function t6CompanyInput(string $name, string $pan): array
{
    return [
        'name' => $name,
        'pan' => $pan,
        'vat' => $pan,
        'address' => 'Ward 12, Lalitpur',
        'district' => 'Lalitpur',
        'fiscal_year' => '2083/84',
    ];
}

/** @param array<string, mixed> $attributes */
function t6MockGoogleUser(array $attributes): void
{
    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->once()->andReturn(SocialiteUser::fake($attributes));

    Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
}

it('renders all signup fields, sends verification mail, and gates application pages', function () {
    Notification::fake();

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('name="name"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false);

    $this->post(route('register.store'), [
        'name' => 'Acceptance Signup',
        'email' => 'acceptance-signup@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors()->assertRedirect(route('verification.notice', absolute: false));

    $user = User::where('email', 'acceptance-signup@example.com')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Verify your email address');

    $unverifiedGateUser = User::factory()->unverified()->create();

    $this->actingAs($unverifiedGateUser)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));

    session()->forget('url.intended');

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect(route('companies.create', absolute: false).'?verified=1');

    Event::assertDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('lets a Google-only account reach its workspace without a local password', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('Continue with Google');

    t6MockGoogleUser([
        'name' => 'Google Acceptance Owner',
        'email' => 'google-acceptance@example.com',
        'email_verified' => true,
    ]);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('companies.create', absolute: false));

    $user = User::where('email', 'google-acceptance@example.com')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->password)->toBeNull();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertRedirect(route('dashboard', absolute: false));

    Livewire::actingAs($user)
        ->test(CompanyCreate::class)
        ->set(t6CompanyInput('Google Builders', '601000001'))
        ->call('createCompany')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Financial overview')
        ->assertSee('Google Builders');

    expect($user->fresh()->password)->toBeNull();
});

it('resets an email password and rejects the old credential', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'reset-acceptance@example.com',
        'password' => 'old-password',
    ]);

    $this->get(route('password.request'))->assertOk()->assertSee('Email password reset link');

    $this->post(route('password.request'), ['email' => $user->email])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $this->get(route('password.reset', $notification->token))->assertOk();

        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login', absolute: false));

        return true;
    });

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'old-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'new-password',
    ])->assertRedirect(route('companies.create', absolute: false));

    $this->assertAuthenticatedAs($user->fresh());
});

it('creates an empty workspace with intentional dashboard and module empty states', function () {
    $user = User::factory()->create();
    $company = app(CompanySetupService::class)->createForUser(
        $user,
        t6CompanyInput('Empty Acceptance Builders', '602000002')
    );

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Empty Acceptance Builders')
        ->assertSee('Your workspace is ready')
        ->assertSee(Npr::format(0), false)
        ->assertSee('Create your first project')
        ->assertSee('Add your first contractor')
        ->assertSee('Record your first payment');

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertSee('No projects yet')
        ->assertSee('Add your first project');

    $this->actingAs($user)
        ->get(route('contractors.index'))
        ->assertOk()
        ->assertSee('No contractors found')
        ->assertSee('Add contractor');

    $this->actingAs($user)
        ->get(route('payments.index'))
        ->assertOk()
        ->assertSee('No payments found')
        ->assertSee('Add your first contractor');

    expect(Project::where('company_id', $company->id)->count())->toBe(0)
        ->and(Contractor::where('company_id', $company->id)->count())->toBe(0)
        ->and(ContractorPayment::where('company_id', $company->id)->count())->toBe(0)
        ->and(app(CalculationService::class)->nextContractorPaymentNo())
        ->toBe('CTP-'.today()->year.'-0001');
});

it('prints and exports only the active company identity and rows', function () {
    $alpha = t6Workspace('Alpha', '603000003');
    $beta = t6Workspace('Beta', '604000004');

    app(TenantService::class)->setCurrentCompany($alpha['company']);

    $this->actingAs($alpha['user'])
        ->get(route('print.payment', $alpha['payment']))
        ->assertOk()
        ->assertSee($alpha['company']->name)
        ->assertSee('PAN '.$alpha['company']->pan)
        ->assertSee($alpha['payment']->payment_no)
        ->assertDontSee($beta['company']->name)
        ->assertDontSee($beta['payment']->payment_no);

    $this->actingAs($alpha['user'])
        ->get(route('print.contractor-ledger', $alpha['contractor']))
        ->assertOk()
        ->assertSee($alpha['company']->name)
        ->assertSee('PAN '.$alpha['company']->pan)
        ->assertSee($alpha['payment']->payment_no)
        ->assertDontSee($beta['contractor']->name);

    $this->actingAs($alpha['user'])
        ->get(route('print.project-payments', $alpha['project']))
        ->assertOk()
        ->assertSee($alpha['company']->name)
        ->assertSee('PAN '.$alpha['company']->pan)
        ->assertSee($alpha['project']->name)
        ->assertDontSee($beta['project']->name);

    $content = $this->actingAs($alpha['user'])
        ->get(route('payments.export'))
        ->assertOk()
        ->streamedContent();

    expect($content)
        ->toContain('Company PAN')
        ->toContain($alpha['company']->name)
        ->toContain($alpha['company']->pan)
        ->toContain($alpha['payment']->payment_no)
        ->not->toContain($beta['company']->name)
        ->not->toContain($beta['payment']->payment_no);
});

it('onboards a second verified account into an empty isolated workspace', function () {
    $alpha = t6Workspace('Alpha', '605000005');

    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Beta Second Owner',
        'email' => 'beta-second@acceptance.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $betaUser = User::where('email', 'beta-second@acceptance.test')->firstOrFail();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $betaUser->id, 'hash' => sha1($betaUser->email)],
    );

    $this->actingAs($betaUser)
        ->get($verificationUrl)
        ->assertRedirect(route('companies.create', absolute: false).'?verified=1');

    Livewire::actingAs($betaUser->fresh())
        ->test(CompanyCreate::class)
        ->set(t6CompanyInput('Beta Second Builders', '606000006'))
        ->call('createCompany')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $betaUser->refresh();
    $betaCompany = Company::findOrFail($betaUser->current_company_id);

    expect($betaCompany->name)->toBe('Beta Second Builders')
        ->and($betaCompany->is_demo)->toBeFalse()
        ->and(CompanyUser::where('user_id', $betaUser->id)->where('company_id', $betaCompany->id)->count())->toBe(1)
        ->and(Project::where('company_id', $betaCompany->id)->count())->toBe(0);

    $this->actingAs($betaUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Beta Second Builders')
        ->assertSee('Your workspace is ready')
        ->assertDontSee($alpha['company']->name)
        ->assertDontSee($alpha['project']->name)
        ->assertDontSee($alpha['contractor']->name)
        ->assertDontSee($alpha['payment']->payment_no)
        ->assertDontSee(Npr::format(2500000), false);
});

it('denies guessed company A URLs, exports, and prints from company B', function () {
    $alpha = t6Workspace('Alpha', '607000007');
    $beta = t6Workspace('Beta', '608000008');

    $guessedRoutes = [
        ['projects.show', $alpha['project']],
        ['projects.edit', $alpha['project']],
        ['projects.jobs', $alpha['project']],
        ['projects.payments', $alpha['project']],
        ['projects.boq', $alpha['project']],
        ['projects.milestones', $alpha['project']],
        ['projects.dsr', $alpha['project']],
        ['projects.reconciliation', $alpha['project']],
        ['projects.running-bills', $alpha['project']],
        ['jobs.show', $alpha['job']],
        ['jobs.edit', $alpha['job']],
        ['jobs.payments.create', $alpha['job']],
        ['contractors.show', $alpha['contractor']],
        ['contractors.edit', $alpha['contractor']],
        ['payments.show', $alpha['payment']],
        ['payments.edit', $alpha['payment']],
        ['print.payment', $alpha['payment']],
        ['print.contractor-ledger', $alpha['contractor']],
        ['print.project-payments', $alpha['project']],
    ];

    foreach ($guessedRoutes as [$routeName, $model]) {
        $this->actingAs($beta['user'])
            ->get(route($routeName, $model))
            ->assertNotFound();
    }

    $content = $this->actingAs($beta['user'])
        ->get(route('payments.export'))
        ->assertOk()
        ->streamedContent();

    expect($content)
        ->not->toContain($alpha['company']->name)
        ->not->toContain($alpha['company']->pan)
        ->not->toContain($alpha['payment']->payment_no)
        ->not->toContain($alpha['project']->name)
        ->not->toContain($alpha['contractor']->name);

    $listRoutes = [
        'projects.index',
        'contractors.index',
        'payments.index',
        'employees.index',
        'attendance.index',
        'vendors.index',
        'requisitions.index',
        'purchase-orders.index',
        'warehouses.index',
        'receipts.index',
        'issues.index',
        'equipment.index',
        'vehicles.index',
        'documents.index',
        'reports.project-summary',
        'reports.reconciliation',
        'reports.po-status',
        'reports.attendance-summary',
        'reports.milestone-progress',
    ];

    foreach ($listRoutes as $routeName) {
        expect(Route::has($routeName))->toBeTrue();

        $this->actingAs($beta['user'])
            ->get(route($routeName))
            ->assertOk()
            ->assertDontSee($alpha['company']->name)
            ->assertDontSee($alpha['project']->name)
            ->assertDontSee($alpha['contractor']->name)
            ->assertDontSee($alpha['payment']->payment_no);
    }
});

it('does not hydrate a replayed Livewire model from another company', function () {
    $alpha = t6Workspace('Alpha', '609000009');
    $beta = t6Workspace('Beta', '610000010');

    app(TenantService::class)->forget();

    $snapshot = Livewire::actingAs($alpha['user'])
        ->test(ProjectShow::class, ['project' => $alpha['project']])
        ->snapshot;

    app(TenantService::class)->setCurrentCompany($beta['company']);

    expect((new Project)->newQueryForRestoration($alpha['project']->id)->first())->toBeNull();

    $response = $this->actingAs($beta['user'])
        ->withHeader('X-Livewire', true)
        ->post(route('default-livewire.update'), [
            'components' => [[
                'snapshot' => json_encode($snapshot),
                'calls' => [],
                'updates' => [],
            ]],
        ]);

    expect($response->getStatusCode())->toBeGreaterThanOrEqual(400)
        ->and($response->getContent())->not->toContain($alpha['project']->name);
});

it('keeps company A audit rows unreadable and unwritable from company B', function () {
    $alpha = t6Workspace('Alpha', '611000011');

    $alphaAudit = AuditLog::where('entity_type', Project::class)
        ->where('entity_id', $alpha['project']->id)
        ->firstOrFail();

    expect($alphaAudit->company_id)->toBe($alpha['company']->id);

    $betaCompany = Company::create([
        'name' => 'Beta Audit Builders',
        'slug' => 'beta-audit-builders',
        'pan' => '612000012',
        'address' => 'Lalitpur',
        'district' => 'Lalitpur',
        'fiscal_year' => '2083/84',
    ]);
    $betaUser = User::factory()->create([
        'email' => 'beta-audit@acceptance.test',
        'current_company_id' => $betaCompany->id,
    ]);
    CompanyUser::create([
        'company_id' => $betaCompany->id,
        'user_id' => $betaUser->id,
        'role' => CompanyRole::Owner,
        'joined_at' => now(),
    ]);

    app(TenantService::class)->setCurrentCompany($betaCompany);

    expect(AuditLog::find($alphaAudit->id))->toBeNull()
        ->and(AuditLog::where('company_id', $alpha['company']->id)->count())->toBe(0);

    $updated = AuditLog::whereKey($alphaAudit->id)->update(['action' => 'updated']);

    expect($updated)->toBe(0)
        ->and($alphaAudit->fresh()->action->value)->toBe('created')
        ->and(AuditLog::whereKey($alphaAudit->id)->delete())->toBe(0);

    $betaProject = Project::create([
        'company_id' => $betaCompany->id,
        'code' => 'PRJ-BETA-AUDIT',
        'name' => 'Beta Audit Project',
        'client_name' => 'Beta Client',
        'contract_value_paisa' => 10000000,
        'location_ward' => '1',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $betaUser->id,
        'advance_paisa' => 0,
        'status' => ProjectStatus::Active,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'company_id' => $betaCompany->id,
        'entity_type' => Project::class,
        'entity_id' => $betaProject->id,
    ]);
});

it('offers no company switcher or join control in the shell', function () {
    $alpha = t6Workspace('Alpha', '613000013');
    $beta = t6Workspace('Beta', '614000014');

    expect(Route::has('companies.switch'))->toBeFalse()
        ->and(Route::has('companies.join'))->toBeFalse()
        ->and(Route::has('companies.index'))->toBeFalse();

    $this->actingAs($beta['user'])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($beta['company']->name)
        ->assertDontSee($alpha['company']->name)
        ->assertDontSee('Switch company')
        ->assertDontSee('Join company')
        ->assertDontSee('name="company_id"', false);
});

it('hides the demo reset action when demo resets are disabled', function () {
    $this->seed(DemoResetSeeder::class);

    config(['demo.reset_enabled' => false]);

    $this->actingAs(User::where('email', 'admin@ranta.test')->sole())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Reset demo');
});
