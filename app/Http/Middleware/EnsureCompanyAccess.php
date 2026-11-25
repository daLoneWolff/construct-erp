<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app(TenantService::class);

        if ($tenant->currentCompany() === null) {
            return redirect()->route('companies.create');
        }

        if (! $tenant->isOwnerOrAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
