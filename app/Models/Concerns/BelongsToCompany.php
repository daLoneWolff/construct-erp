<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    /**
     * Scope every query to the resolved current company. When no company is
     * resolved (console, seeding, onboarding), the legacy unscoped behavior is
     * preserved so the seeded demo keeps working.
     */
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            $companyId = app(TenantService::class)->currentCompanyId();

            if ($companyId !== null) {
                $builder->where(
                    $builder->getModel()->qualifyColumn('company_id'),
                    $companyId
                );
            }
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('company_id') !== null) {
                return;
            }

            $companyId = app(TenantService::class)->currentCompanyId();

            if ($companyId !== null) {
                $model->setAttribute('company_id', $companyId);
            }
        });
    }

    /**
     * Livewire restores model properties with `newQueryForRestoration()`, which
     * bypasses global scopes by default. Keep tenant scoping on that path so a
     * replayed component snapshot cannot hydrate another company's record.
     *
     * @param  array<int, mixed>|int  $ids
     * @return Builder<static>
     */
    public function newQueryForRestoration($ids)
    {
        return $this->newQuery()->whereKey($ids);
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
