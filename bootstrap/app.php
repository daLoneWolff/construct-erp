<?php

use App\Http\Middleware\EnsureCompanyAccess;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ResolveCurrentCompany;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'resolve-company' => ResolveCurrentCompany::class,
            'company.access' => EnsureCompanyAccess::class,
        ]);

        // Company resolution must happen before implicit route binding so a
        // foreign record is rejected by the tenant scope instead of being
        // bound and then rendered with null cross-company relations.
        $middleware->appendToPriorityList(
            AuthenticatesRequests::class,
            ResolveCurrentCompany::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
