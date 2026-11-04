<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformAuditLogController extends Controller
{
    /**
     * Display a paginated listing of platform audit logs.
     */
    public function index(Request $request): View
    {
        $query = PlatformAuditLog::with(['platformUser', 'tenant'])->latest('id');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('tenant', fn($t) => $t->where('name', 'like', "%{$search}%")->orWhere('tenant_code', 'like', "%{$search}%"))
                  ->orWhereHas('platformUser', fn($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $logs = $query->paginate(20)->withQueryString();
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'tenant_code']);

        $distinctActions = PlatformAuditLog::distinct('action')
            ->pluck('action')
            ->filter()
            ->values();

        return view('platform.audit_logs.index', compact('logs', 'tenants', 'distinctActions'));
    }
}
