<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCodeConfiguration;
use App\Models\Location;
use App\Services\AuditLogService;
use App\Services\CompanyContextService;
use App\Services\EmployeeCodeGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeCodeConfigurationController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected EmployeeCodeGeneratorService $codeGenerator,
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Show Consolidated Employee Code Settings (Configuration & Master Register).
     */
    public function settings(Request $request)
    {
        $this->authorizeAdmin();

        $companyId = $this->companyContext->getActiveCompanyId();
        $configuration = $this->codeGenerator->getConfiguration($companyId);
        $preview = $this->codeGenerator->previewCode($companyId);

        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->get();
        $departments = Department::where('company_id', $companyId)->where('status', 'Active')->get();

        $query = Employee::with([
            'company',
            'location',
            'department',
            'designation',
        ])->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('code_generation_method')) {
            $query->where('code_generation_method', $request->input('code_generation_method'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        $employees = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();
        $activeTab = $request->input('tab', 'config');

        return view('admin.employee_codes.settings', compact(
            'configuration',
            'preview',
            'locations',
            'departments',
            'employees',
            'activeTab'
        ));
    }

    /**
     * Show Employee Code Configuration page.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $companyId = $this->companyContext->getActiveCompanyId();
        $configuration = $this->codeGenerator->getConfiguration($companyId);
        $preview = $this->codeGenerator->previewCode($companyId);

        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->get();
        $departments = Department::where('company_id', $companyId)->where('status', 'Active')->get();

        return view('admin.employee_codes.configuration', compact('configuration', 'preview', 'locations', 'departments'));
    }

    /**
     * Save Employee Code Configuration.
     */
    public function update(Request $request)
    {
        $this->authorizeAdmin();

        $companyId = $this->companyContext->getActiveCompanyId();
        $config = $this->codeGenerator->getConfiguration($companyId);

        $validated = $request->validate([
            'prefix' => ['required', 'string', 'max:20'],
            'company_prefix' => ['nullable', 'string', 'max:20'],
            'branch_prefix' => ['nullable', 'string', 'max:20'],
            'department_prefix' => ['nullable', 'string', 'max:20'],
            'financial_year_prefix' => ['nullable', 'string', 'max:20'],
            'number_length' => ['required', 'integer', 'min:3', 'max:8'],
            'starting_sequence_number' => ['required', 'integer', 'min:1'],
            'separator' => ['nullable', 'string', 'max:5'],
            'format_template' => ['required', 'string', 'max:150'],
            'reset_sequence_annually' => ['nullable', 'boolean'],
            'reset_sequence_per_branch' => ['nullable', 'boolean'],
            'reset_sequence_per_company' => ['nullable', 'boolean'],
            'allow_manual_employee_code' => ['nullable', 'boolean'],
            'uniqueness_scope' => ['required', 'in:company,tenant,global'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldValues = $config->toArray();

        $config->update([
            'prefix' => trim($validated['prefix']),
            'company_prefix' => !empty($validated['company_prefix']) ? trim($validated['company_prefix']) : null,
            'branch_prefix' => !empty($validated['branch_prefix']) ? trim($validated['branch_prefix']) : null,
            'department_prefix' => !empty($validated['department_prefix']) ? trim($validated['department_prefix']) : null,
            'financial_year_prefix' => !empty($validated['financial_year_prefix']) ? trim($validated['financial_year_prefix']) : null,
            'number_length' => $validated['number_length'],
            'starting_sequence_number' => $validated['starting_sequence_number'],
            'separator' => $validated['separator'] ?? '-',
            'format_template' => trim($validated['format_template']),
            'reset_sequence_annually' => $request->boolean('reset_sequence_annually'),
            'reset_sequence_per_branch' => $request->boolean('reset_sequence_per_branch'),
            'reset_sequence_per_company' => $request->boolean('reset_sequence_per_company', true),
            'allow_manual_employee_code' => $request->boolean('allow_manual_employee_code'),
            'uniqueness_scope' => $validated['uniqueness_scope'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogService->log(
            action: 'Employee Code Configuration Updated',
            entityType: 'EmployeeCodeConfiguration',
            entityId: $config->id,
            oldValues: $oldValues,
            newValues: $config->fresh()->toArray(),
            companyId: $companyId,
            actor: Auth::user()
        );

        return redirect()->route('admin.employee-codes.index')
            ->with('success', 'Employee Code Configuration updated successfully.');
    }

    /**
     * Preview generated employee code for given template parameters via AJAX.
     */
    public function preview(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $companyId = $this->companyContext->getActiveCompanyId();

        $preview = $this->codeGenerator->previewCode($companyId, [
            'location_id' => $request->input('location_id'),
            'department_id' => $request->input('department_id'),
        ], [
            'prefix' => $request->input('prefix', 'EMP'),
            'company_prefix' => $request->input('company_prefix'),
            'branch_prefix' => $request->input('branch_prefix'),
            'department_prefix' => $request->input('department_prefix'),
            'number_length' => (int) $request->input('number_length', 4),
            'starting_sequence_number' => (int) $request->input('starting_sequence_number', 1001),
            'separator' => $request->input('separator', '-'),
            'format_template' => $request->input('format_template', '{COMPANY}-{PREFIX}-{SEQUENCE}'),
        ]);

        return response()->json([
            'preview' => $preview,
            'status' => 'success',
        ]);
    }

    /**
     * Employee Code Master List page.
     */
    public function master(Request $request)
    {
        $this->authorizeAdmin();

        $companyId = $this->companyContext->getActiveCompanyId();

        $query = Employee::with([
            'company',
            'location',
            'department',
            'designation',
        ])->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('code_generation_method')) {
            $query->where('code_generation_method', $request->input('code_generation_method'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('employment_type')) {
            $query->where('employee_type', $request->input('employment_type'));
        }

        $employees = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->get();
        $departments = Department::where('company_id', $companyId)->where('status', 'Active')->get();

        return view('admin.employee_codes.master', compact('employees', 'locations', 'departments'));
    }

    /**
     * Export Employee Code Master to CSV.
     */
    public function exportMaster(Request $request): StreamedResponse
    {
        $this->authorizeAdmin();

        $companyId = $this->companyContext->getActiveCompanyId();

        $query = Employee::with([
            'company',
            'location',
            'department',
        ])->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $records = $query->orderBy('id', 'asc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employee_code_master_' . date('Y_m_d_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Employee Code',
                'Employee ID',
                'Employee Name',
                'Company',
                'Branch',
                'Department',
                'Employment Type',
                'Date of Joining',
                'Status',
                'Code Generation Method',
                'Created Date',
            ]);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->employee_code,
                    $r->id,
                    $r->full_name,
                    $r->company?->name,
                    $r->location?->name,
                    $r->department?->name,
                    $r->employee_type,
                    $r->date_of_joining?->format('Y-m-d'),
                    $r->status,
                    $r->code_generation_method,
                    $r->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    protected function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (!$user->isHr()) {
            abort(403, 'Unauthorized. Employee Code Configuration is restricted to HR Executive and Administrator roles.');
        }
    }
}
