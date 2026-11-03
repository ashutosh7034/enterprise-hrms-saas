<?php

namespace App\Http\Middleware;

use App\Services\CompanyContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $activeCompany = $this->companyContext->getActiveCompany();
        $accessibleCompanies = $this->companyContext->getAccessibleCompanies();
        $canSwitchCompany = $this->companyContext->canUserSwitchCompany($request->user());

        View::share('activeCompany', $activeCompany);
        View::share('accessibleCompanies', $accessibleCompanies);
        View::share('canSwitchCompany', $canSwitchCompany);

        return $next($request);
    }
}

