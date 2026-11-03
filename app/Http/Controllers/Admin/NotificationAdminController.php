<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\NotificationChannelConfiguration;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationTemplate;
use App\Services\AuditLogService;
use App\Services\CompanyContextService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationAdminController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected CompanyContextService $companyContext,
        protected AuditLogService $auditLog
    ) {}

    /**
     * Notification Control Tower Dashboard.
     */
    public function dashboard(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $user = Auth::user();

        $query = NotificationDeliveryLog::withoutGlobalScopes();
        if ($companyId && !$user->isHoAdmin()) {
            $query->where('company_id', $companyId);
        }

        $totalSent = (clone $query)->whereIn('status', ['dispatched', 'delivered'])->count();
        $totalFailed = (clone $query)->where('status', 'failed')->count();
        $totalQueued = (clone $query)->where('status', 'queued')->count();

        // Breakdown by channel
        $byChannel = (clone $query)
            ->selectRaw('channel, status, count(*) as count')
            ->groupBy('channel', 'status')
            ->get();

        $recentFailed = (clone $query)
            ->where('status', 'failed')
            ->with(['company', 'user', 'employee'])
            ->latest()
            ->take(10)
            ->get();

        $recentDeliveries = (clone $query)
            ->with(['company', 'user', 'employee'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.notifications.dashboard', compact(
            'totalSent',
            'totalFailed',
            'totalQueued',
            'byChannel',
            'recentFailed',
            'recentDeliveries'
        ));
    }

    /**
     * Delivery Logs Archive with filters.
     */
    public function deliveryLogs(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $user = Auth::user();

        $query = NotificationDeliveryLog::withoutGlobalScopes()
            ->with(['company', 'user', 'employee']);

        if ($companyId && !$user->isHoAdmin()) {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->query('channel'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        $logs = $query->latest()->paginate(20)->withQueryString();

        return view('admin.notifications.logs', compact('logs'));
    }

    /**
     * Retry failed notification delivery.
     */
    public function retryLog(Request $request, NotificationDeliveryLog $log)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($log->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(403, 'Unauthorized cross-company notification retry attempt.');
        }

        $result = $this->notificationService->retryFailed($log);

        $this->auditLog->log(
            action: 'NOTIFICATION_RETRY',
            model: $log,
            oldValues: ['status' => 'failed', 'attempts' => $log->attempt_number - 1],
            newValues: ['status' => $log->status, 'attempts' => $log->attempt_number, 'result' => $result['status']]
        );

        if ($result['status'] === 'Success') {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Template Management.
     */
    public function templates(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $user = Auth::user();

        $query = NotificationTemplate::withoutGlobalScopes()->with('company');

        if ($companyId && !$user->isHoAdmin()) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->query('channel'));
        }

        $templates = $query->orderBy('category')->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.notifications.templates.index', compact('templates'));
    }

    /**
     * Edit template.
     */
    public function editTemplate(NotificationTemplate $template)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($template->company_id && $template->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(403);
        }

        return view('admin.notifications.templates.edit', compact('template'));
    }

    /**
     * Update template.
     */
    public function updateTemplate(Request $request, NotificationTemplate $template)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($template->company_id && $template->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'body_template' => 'required|string',
            'action_url' => 'nullable|string|max:255',
            'is_mandatory' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $old = $template->only(['subject', 'body_template', 'action_url', 'is_mandatory', 'is_active']);

        $template->update([
            'subject' => $validated['subject'] ?? null,
            'body_template' => $validated['body_template'],
            'action_url' => $validated['action_url'] ?? null,
            'is_mandatory' => (bool) ($request->input('is_mandatory', 0)),
            'is_active' => (bool) ($request->input('is_active', 0)),
        ]);

        $this->auditLog->log(
            action: 'NOTIFICATION_TEMPLATE_UPDATE',
            model: $template,
            oldValues: $old,
            newValues: $template->only(['subject', 'body_template', 'action_url', 'is_mandatory', 'is_active'])
        );

        return redirect()->route('admin.notifications.templates.index')->with('success', 'Notification template updated successfully.');
    }

    /**
     * Preview template.
     */
    public function previewTemplate(NotificationTemplate $template)
    {
        $dummyParams = [
            'employee_name' => 'Amit Sharma',
            'employee_code' => 'VE-EMP-1001',
            'company_name' => 'Vishal Enterprise',
            'company_code' => 'VE',
            'action_url' => url('/dashboard'),
            'attendance_date' => date('Y-m-d'),
            'new_status' => 'Present',
            'regularization_type' => 'Missed Punch',
            'leave_type' => 'Privilege Leave',
            'calculated_days' => '2',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+2 days')),
            'period_month' => date('F Y'),
            'net_pay' => '48,500.00',
            'principal_amount' => '50,000.00',
            'monthly_installment' => '5,000.00',
            'net_payable' => '1,25,000.00',
            'remarks' => 'Approved by competent authority',
        ];

        $rendered = $template->render($dummyParams);

        if ($template->channel === 'email') {
            return view('emails.hrms_notification', [
                'subjectText' => $rendered['subject'] ?? $template->name,
                'bodyText' => $rendered['body'],
                'companyName' => 'Vishal Enterprise',
                'employeeName' => 'Amit Sharma',
                'actionUrl' => $rendered['action_url'],
                'category' => $template->category,
                'companyCode' => 'VE',
            ]);
        }

        return view('admin.notifications.templates.preview', compact('template', 'rendered'));
    }

    /**
     * Channel configurations.
     */
    public function channels(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $user = Auth::user();

        $query = NotificationChannelConfiguration::withoutGlobalScopes()->with('company');

        if ($companyId && !$user->isHoAdmin()) {
            $query->where('company_id', $companyId);
        }

        $channels = $query->orderBy('company_id')->orderBy('channel')->get();
        $companies = Company::where('status', 'Active')->get();

        return view('admin.notifications.channels.index', compact('channels', 'companies'));
    }

    /**
     * Update channel configuration.
     */
    public function updateChannel(Request $request, NotificationChannelConfiguration $channel)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($channel->company_id && $channel->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'is_mock' => 'nullable|boolean',
            'provider_name' => 'required|string|max:50',
            'credentials' => 'nullable|string',
        ]);

        $old = $channel->only(['is_enabled', 'is_mock', 'provider_name']);

        $channel->update([
            'is_enabled' => (bool) $request->input('is_enabled', 0),
            'is_mock' => (bool) $request->input('is_mock', 0),
            'provider_name' => $validated['provider_name'],
            'credentials' => $validated['credentials'] ?? $channel->credentials,
        ]);

        $this->auditLog->log(
            action: 'NOTIFICATION_CHANNEL_UPDATE',
            model: $channel,
            oldValues: $old,
            newValues: $channel->only(['is_enabled', 'is_mock', 'provider_name'])
        );

        return back()->with('success', "{$channel->channel} configuration updated successfully.");
    }
}
