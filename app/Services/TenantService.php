<?php

namespace App\Services;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;

class TenantService
{
    /** Current request's resolved company, shared across all model scopes. */
    private static ?Company $currentCompany = null;

    private static bool $resolved = false;

    /**
     * Resolve the active company from the authenticated user's server-side
     * current_company_id plus a verified company_user membership.
     */
    public function currentCompany(): ?Company
    {
        if (self::$resolved) {
            return self::$currentCompany;
        }

        return $this->resolveFromAuth();
    }

    public function currentCompanyId(): ?int
    {
        return $this->currentCompany()?->id;
    }

    /**
     * Set the request's company explicitly. Middleware verifies membership
     * before calling this; test helpers may set a fixture company directly.
     */
    public function setCurrentCompany(?Company $company): void
    {
        self::$currentCompany = $company;
        self::$resolved = true;
    }

    /** Clear the request-scoped cache between tests/requests. */
    public function forget(): void
    {
        self::$currentCompany = null;
        self::$resolved = false;
    }

    /** Owner/Admin membership is required for every company-scoped screen. */
    public function isOwnerOrAdmin(): bool
    {
        return $this->hasRole(CompanyRole::Owner, CompanyRole::Admin);
    }

    public function isContractor(): bool
    {
        return $this->hasRole(CompanyRole::Contractor);
    }

    public function hasCompanyAccess(): bool
    {
        return $this->hasRole(CompanyRole::Owner, CompanyRole::Admin, CompanyRole::Contractor);
    }

    public function hasRole(CompanyRole ...$roles): bool
    {
        $company = $this->currentCompany();
        $user = auth()->user();

        if (! $user instanceof User || $company === null) {
            return false;
        }

        $membership = $user->companies()
            ->whereKey($company->id)
            ->first();

        if ($membership === null) {
            return false;
        }

        $role = $membership->pivot->role;

        return in_array($role, $roles, true);
    }

    private function resolveFromAuth(): ?Company
    {
        $user = auth()->user();

        if (! $user instanceof User || $user->current_company_id === null) {
            return null;
        }

        $company = Company::query()->find($user->current_company_id);

        if ($company === null || ! $user->companies()->whereKey($company->id)->exists()) {
            return null;
        }

        self::$currentCompany = $company;
        self::$resolved = true;

        return $company;
    }
}
