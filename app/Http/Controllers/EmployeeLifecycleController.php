<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Services\CompanyContextService;
use App\Services\EmployeeLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeLifecycleController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected EmployeeLifecycleService $lifecycleService
    ) {}

    /**
     * Convert employee employment type (e.g. Intern to Permanent).
     */
    public function convertEmploymentType(Request $request, Employee $employee)
    {
        $this->authorizeAccess($employee);

        $companyId = $this->companyContext->getActiveCompanyId();

        if ($request->filled('to_employment_type') && !$request->filled('new_employment_type')) {
            $request->merge(['new_employment_type' => $request->input('to_employment_type')]);
        }
        if ($request->filled('effective_date') && !$request->filled('conversion_effective_date')) {
            $request->merge(['conversion_effective_date' => $request->input('effective_date')]);
        }

        $validated = $request->validate([
            'conversion_effective_date' => ['required', 'date'],
            'new_employment_type' => ['required', 'string', 'in:Permanent,Contract,Probationary,Trainee,Intern'],
            'to_designation_id' => ['nullable', Rule::exists('designations', 'id')],
            'to_department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'to_location_id' => ['nullable', Rule::exists('locations', 'id')->where('company_id', $companyId)],
            'new_salary_structure' => ['nullable', 'string', 'max:255'],
            'confirmation_required' => ['nullable', 'boolean'],
            'probation_end_date' => ['nullable', 'date', 'after:conversion_effective_date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->lifecycleService->convertEmploymentType($employee, $validated, Auth::user());

        return redirect()->to(route('employees.show', $employee->id) . '#lifecycle')
            ->with('success', "Employment type converted successfully for {$employee->employee_code}.");
    }

    /**
     * Confirm employee from probation.
     */
    public function confirmEmployee(Request $request, Employee $employee)
    {
        $this->authorizeAccess($employee);

        if ($request->filled('effective_date') && !$request->filled('confirmation_effective_date')) {
            $request->merge(['confirmation_effective_date' => $request->input('effective_date')]);
        }

        $validated = $request->validate([
            'confirmation_effective_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'confirmation_letter' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $file = $request->file('confirmation_letter');
        $this->lifecycleService->confirmEmployee($employee, $validated, Auth::user(), $file);

        return redirect()->to(route('employees.show', $employee->id) . '#lifecycle')
            ->with('success', "Employee {$employee->employee_code} has been successfully confirmed.");
    }

    /**
     * Extend employee probation.
     */
    public function extendProbation(Request $request, Employee $employee)
    {
        $this->authorizeAccess($employee);

        if ($request->filled('extended_until') && !$request->filled('new_probation_end_date')) {
            $request->merge(['new_probation_end_date' => $request->input('extended_until')]);
        }

        $validated = $request->validate([
            'new_probation_end_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->lifecycleService->extendProbation($employee, $validated, Auth::user());

        return redirect()->to(route('employees.show', $employee->id) . '#lifecycle')
            ->with('success', "Probation period extended for {$employee->employee_code} until {$validated['new_probation_end_date']}.");
    }

    /**
     * Branch & Department Transfer.
     */
    public function branchTransfer(Request $request, Employee $employee)
    {
        $this->authorizeAccess($employee);

        $companyId = $this->companyContext->getActiveCompanyId();

        if ($request->filled('effective_date') && !$request->filled('transfer_effective_date')) {
            $request->merge(['transfer_effective_date' => $request->input('effective_date')]);
        }
        if ($request->filled('expected_return_date') && !$request->filled('transfer_end_date')) {
            $request->merge(['transfer_end_date' => $request->input('expected_return_date')]);
        }

        $validated = $request->validate([
            'to_location_id' => ['required', Rule::exists('locations', 'id')->where('company_id', $companyId)],
            'to_department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'to_designation_id' => ['nullable', Rule::exists('designations', 'id')],
            'to_reporting_manager_id' => ['nullable', Rule::exists('employees', 'id')->where('company_id', $companyId)->whereNot('id', $employee->id)],
            'transfer_type' => ['required', 'in:Permanent,Temporary'],
            'transfer_effective_date' => ['required', 'date'],
            'transfer_end_date' => ['nullable', 'date', 'after:transfer_effective_date'],
            'reason' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->lifecycleService->branchTransfer($employee, $validated, Auth::user());

        return redirect()->to(route('employees.show', $employee->id) . '#lifecycle')
            ->with('success', "Branch transfer initiated for {$employee->employee_code}.");
    }

    /**
     * Authorized Date of Joining update.
     */
    public function updateDateOfJoining(Request $request, Employee $employee)
    {
        $this->authorizeAccess($employee);

        $validated = $request->validate([
            'date_of_joining' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->lifecycleService->updateDateOfJoining($employee, $validated['date_of_joining'], $validated['reason'], Auth::user());

        return redirect()->to(route('employees.show', $employee->id) . '#lifecycle')
            ->with('success', "Date of Joining updated and recorded in audit log.");
    }

    /**
     * Export complete Employee Master List to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = Employee::with([
            'location',
            'joiningLocation',
            'department',
            'joiningDepartment',
            'designation',
            'joiningDesignation',
            'reportingManager',
            'company',
        ])->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->input('designation_id'));
        }

        if ($request->filled('employment_type')) {
            $query->where('employee_type', $request->input('employment_type'));
        }

        if ($request->filled('confirmation_status')) {
            $query->where('confirmation_status', $request->input('confirmation_status'));
        }

        $employees = $query->orderBy('id', 'asc')->get();

        if ($request->input('format') === 'xlsx') {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Workforce Master');

            $columns = [
                'Employee Code', 'Employee ID', 'Full Name', 'Company', 'Joining Branch',
                'Current Branch', 'Joining Department', 'Current Department', 'Joining Designation',
                'Current Designation', 'Employment Type', 'Date of Joining', 'Confirmation Status',
                'Confirmation Date', 'Probation End Date', 'Reporting Manager', 'Employment Status',
                'Code Generation Method', 'Gate Pass Number', 'Email', 'Phone',
            ];

            $sheet->fromArray($columns, null, 'A1');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D9488']
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ];
            $sheet->getStyle('A1:U1')->applyFromArray($headerStyle);
            $sheet->getRowDimension(1)->setRowHeight(26);

            $rowIdx = 2;
            foreach ($employees as $emp) {
                $sheet->fromArray([
                    $emp->employee_code,
                    $emp->id,
                    $emp->full_name,
                    $emp->company?->name,
                    $emp->joiningLocation?->name ?: $emp->location?->name,
                    $emp->location?->name,
                    $emp->joiningDepartment?->name ?: $emp->department?->name,
                    $emp->department?->name,
                    $emp->joiningDesignation?->title ?: $emp->designation?->title,
                    $emp->designation?->title,
                    $emp->employee_type,
                    $emp->date_of_joining?->format('Y-m-d'),
                    $emp->confirmation_status,
                    $emp->confirmation_date?->format('Y-m-d'),
                    $emp->probation_end_date?->format('Y-m-d'),
                    $emp->reportingManager?->full_name,
                    $emp->status,
                    $emp->code_generation_method,
                    $emp->gate_pass_number,
                    $emp->email,
                    $emp->phone,
                ], null, "A{$rowIdx}");
                $rowIdx++;
            }

            foreach (range('A', 'U') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $fileName = 'employee_master_' . date('Y_m_d_His') . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employee_master_' . date('Y_m_d_His') . '.csv"',
        ];

        return response()->stream(function () use ($employees) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'Employee Code',
                'Employee ID',
                'Full Name',
                'Company',
                'Joining Branch',
                'Current Branch',
                'Joining Department',
                'Current Department',
                'Joining Designation',
                'Current Designation',
                'Employment Type',
                'Date of Joining',
                'Confirmation Status',
                'Confirmation Date',
                'Probation End Date',
                'Reporting Manager',
                'Employment Status',
                'Code Generation Method',
                'Gate Pass Number',
                'Email',
                'Phone',
            ]);

            foreach ($employees as $emp) {
                fputcsv($handle, [
                    $emp->employee_code,
                    $emp->id,
                    $emp->full_name,
                    $emp->company?->name,
                    $emp->joiningLocation?->name ?: $emp->location?->name,
                    $emp->location?->name,
                    $emp->joiningDepartment?->name ?: $emp->department?->name,
                    $emp->department?->name,
                    $emp->joiningDesignation?->title ?: $emp->designation?->title,
                    $emp->designation?->title,
                    $emp->employee_type,
                    $emp->date_of_joining?->format('Y-m-d'),
                    $emp->confirmation_status,
                    $emp->confirmation_date?->format('Y-m-d'),
                    $emp->probation_end_date?->format('Y-m-d'),
                    $emp->reportingManager?->full_name,
                    $emp->status,
                    $emp->code_generation_method,
                    $emp->gate_pass_number,
                    $emp->email,
                    $emp->phone,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    protected function authorizeAccess(Employee $employee): void
    {
        $user = Auth::user();
        if (!$user->isHr()) {
            abort(403, 'Unauthorized. Employee lifecycle management requires HR Executive or Administrator privileges.');
        }

        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }
    }
}
