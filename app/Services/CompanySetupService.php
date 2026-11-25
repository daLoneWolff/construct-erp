<?php

namespace App\Services;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanySetupService
{
    /**
     * Create the first workspace for a registered user. The company, owner
     * membership and active-company pointer move together or not at all.
     *
     * @param  array{name: string, pan: string, vat?: string|null, address: string, district: string, fiscal_year: string}  $input
     */
    public function createForUser(User $user, array $input): Company
    {
        if ($user->current_company_id !== null || $user->ownedCompany()->exists() || $user->companies()->exists()) {
            throw ValidationException::withMessages([
                'name' => __('This account already has a company workspace.'),
            ]);
        }

        return DB::transaction(function () use ($user, $input): Company {
            $now = now();

            $company = Company::create([
                'name' => $input['name'],
                'slug' => $this->uniqueSlug($input['name']),
                'owner_user_id' => $user->id,
                'pan' => $input['pan'],
                'vat' => $input['vat'] ?? null,
                'address' => $input['address'],
                'district' => $input['district'],
                'fiscal_year' => $input['fiscal_year'],
                'logo_mock' => null,
                'onboarding_completed_at' => $now,
                'is_demo' => false,
            ]);

            CompanyUser::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'role' => CompanyRole::Owner,
                'joined_at' => $now,
            ]);

            $user->forceFill([
                'current_company_id' => $company->id,
            ])->save();

            return $company;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;
        $suffix = 2;

        while (Company::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
