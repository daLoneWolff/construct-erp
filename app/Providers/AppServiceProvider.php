<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\BoqItem;
use App\Models\DailySiteReport;
use App\Models\MaterialRequisition;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Models\PurchaseOrder;
use App\Models\RunningBill;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\User;
use App\Models\Vendor;
use App\Observers\AuditObserver;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureAuditing();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Carbon::setToStringFormat('d/m/Y');
        CarbonImmutable::setToStringFormat('d/m/Y');

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * The app has one admin role. Ability hooks remain available to existing
     * screens, but they are always granted; there are no role-specific pages.
     */
    protected function configureAuthorization(): void
    {
        foreach (['view', 'write', 'approve', 'receive', 'certify', 'manage', 'reset-demo'] as $ability) {
            Gate::define($ability, fn (User $user) => true);
        }
    }

    /**
     * Audit every tracked entity. Payment records are included because they
     * are the financial source of truth for contractor balances.
     */
    protected function configureAuditing(): void
    {
        $tracked = [
            MaterialRequisition::class,
            PurchaseOrder::class,
            RunningBill::class,
            DailySiteReport::class,
            Attendance::class,
            BoqItem::class,
            Vendor::class,
            Project::class,
            Contractor::class,
            ProjectJob::class,
            ContractorPayment::class,
        ];

        foreach ($tracked as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
