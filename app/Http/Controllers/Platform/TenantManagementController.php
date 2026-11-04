<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantManagementController extends Controller
{
    /**
     * Display a listing of customer tenants.
     */
    public function index(Request $request): View
    {
        $query = Tenant::withCount('companies')->latest();

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('tenant_code', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            if (in_array($status, ['active', 'suspended', 'inactive'])) {
                $query->where('status', $status);
            }
        }

        $tenants = $query->paginate(15)->withQueryString();

        $counts = [
            'total' => Tenant::count(),
            'active' => Tenant::where('status', 'active')->count(),
            'suspended' => Tenant::where('status', 'suspended')->count(),
            'inactive' => Tenant::where('status', 'inactive')->count(),
        ];

        return view('platform.tenants.index', compact('tenants', 'counts'));
    }

    /**
     * Show the form for creating a new customer tenant.
     */
    public function create(): View
    {
        return view('platform.tenants.create');
    }

    /**
     * Store a newly created customer tenant in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_code' => ['required', 'string', 'max:30', 'unique:tenants,tenant_code', 'alpha_dash'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'unique:tenants,slug', 'alpha_dash'],
            'status' => ['required', 'in:active,suspended,inactive'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['tenant_code'] = Str::upper($validated['tenant_code']);
        $validated['slug'] = Str::lower($validated['slug']);

        $tenant = Tenant::create($validated);

        PlatformAuditLog::create([
            'platform_user_id' => Auth::guard('platform')->id(),
            'tenant_id' => $tenant->id,
            'action' => 'TENANT_CREATED',
            'metadata' => [
                'tenant_code' => $tenant->tenant_code,
                'name' => $tenant->name,
                'status' => $tenant->status,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('platform.tenants.show', $tenant)
            ->with('success', "Customer tenant '{$tenant->name}' ({$tenant->tenant_code}) was successfully created.");
    }

    /**
     * Display the specified customer tenant details and associated legal companies.
     */
    public function show(Tenant $tenant): View
    {
        $tenant->load(['companies' => function ($q) {
            $q->select('id', 'tenant_id', 'name', 'code', 'cin_number', 'email', 'phone', 'status', 'created_at');
        }]);

        $subscription = $tenant->latestSubscription();
        if ($subscription) {
            $subscription->load(['plan.features', 'invoices.payments']);
        }

        $auditLogs = PlatformAuditLog::where('tenant_id', $tenant->id)
            ->with('platformUser:id,name,email')
            ->latest()
            ->take(15)
            ->get();

        return view('platform.tenants.show', compact('tenant', 'subscription', 'auditLogs'));
    }

    /**
     * Show the form for editing the customer tenant.
     */
    public function edit(Tenant $tenant): View
    {
        return view('platform.tenants.edit', compact('tenant'));
    }

    /**
     * Update the specified customer tenant.
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_code' => ['required', 'string', 'max:30', Rule::unique('tenants')->ignore($tenant->id), 'alpha_dash'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', Rule::unique('tenants')->ignore($tenant->id), 'alpha_dash'],
            'status' => ['required', 'in:active,suspended,inactive'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['tenant_code'] = Str::upper($validated['tenant_code']);
        $validated['slug'] = Str::lower($validated['slug']);

        $oldStatus = $tenant->status;
        $tenant->update($validated);

        PlatformAuditLog::create([
            'platform_user_id' => Auth::guard('platform')->id(),
            'tenant_id' => $tenant->id,
            'action' => 'TENANT_UPDATED',
            'metadata' => [
                'updated_fields' => array_keys($validated),
                'old_status' => $oldStatus,
                'new_status' => $tenant->status,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('platform.tenants.show', $tenant)
            ->with('success', "Tenant '{$tenant->name}' details updated successfully.");
    }

    /**
     * Update the status of a customer tenant (active, suspended, inactive).
     */
    public function updateStatus(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,suspended,inactive'],
        ]);

        $oldStatus = $tenant->status;
        $tenant->update(['status' => $validated['status']]);

        PlatformAuditLog::create([
            'platform_user_id' => Auth::guard('platform')->id(),
            'tenant_id' => $tenant->id,
            'action' => 'TENANT_STATUS_CHANGED',
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Tenant status changed to '{$validated['status']}' successfully.");
    }
}

