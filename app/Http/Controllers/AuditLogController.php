<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request): View
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        $query = AuditLog::with('user')
            ->where('company_id', $activeCompanyId)
            ->latest('id');

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('auditable_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $logs = $query->paginate(20)->withQueryString();
        $activeCompany = $this->companyContext->getActiveCompany();

        return view('audit_logs.index', compact('logs', 'activeCompany'));
    }
}

