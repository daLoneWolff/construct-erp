<?php

use App\Enums\CompanyRole;
use App\Enums\Role;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/** Shared demo-shaped company for tests that exercise tenant-scoped screens. */
function tenantCompany(array $attributes = []): Company
{
    $tenant = app(TenantService::class);
    $existing = $tenant->currentCompany();

    if ($existing !== null) {
        return $existing;
    }

    $company = Company::create(array_merge([
        'name' => 'Ranta Infrastructure Pvt.Ltd',
        'pan' => '601234567',
        'vat' => '601234567',
        'address' => 'Ward 12, Lalitpur',
        'district' => 'Lalitpur',
        'fiscal_year' => '2083/84',
    ], $attributes));

    $tenant->setCurrentCompany($company);

    return $company;
}

/** Owner/admin user with a verified membership in the shared test company. */
function tenantUser(
    string $email,
    array $attributes = [],
    ?Company $company = null,
    CompanyRole $companyRole = CompanyRole::Owner
): User {
    $company = $company ?? tenantCompany();

    $user = User::create(array_merge([
        'name' => 'Tenant Admin',
        'email' => $email,
        'email_verified_at' => now(),
        'password' => 'password',
        'role' => Role::Admin,
        'current_company_id' => $company->id,
    ], $attributes));

    CompanyUser::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'role' => $companyRole,
        'joined_at' => now(),
    ]);

    app(TenantService::class)->setCurrentCompany($company);

    return $user;
}
