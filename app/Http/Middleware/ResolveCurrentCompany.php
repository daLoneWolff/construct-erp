<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app(TenantService::class);
        $tenant->forget();

        $user = $request->user();
        $company = $user instanceof User && $user->current_company_id !== null
            ? Company::query()->find($user->current_company_id)
            : null;

        if ($user instanceof User && $company !== null && $user->companies()->whereKey($company->id)->exists()) {
            $tenant->setCurrentCompany($company);
        }

        return $next($request);
    }
}
