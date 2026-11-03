<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use App\Models\BiometricPunchLog;
use App\Models\Location;
use App\Services\BiometricIntegrationService;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BiometricDeviceController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected BiometricIntegrationService $biometricService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = BiometricDevice::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['location', 'syncLogs' => fn($q) => $q->latest('id')->take(1)]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('health_status')) {
            $query->where('health_status', $request->health_status);
        }

        $devices = $query->orderBy('device_name')->paginate(15);
        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();

        return view('biometrics.devices.index', compact('devices', 'locations'));
    }

    public function create()
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();

        return view('biometrics.devices.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'device_identifier' => [
                'required', 'string', 'max:100',
                Rule::unique('biometric_devices', 'device_identifier')->where('company_id', $companyId),
            ],
            'device_name' => 'required|string|max:150',
            'serial_number' => 'required|string|max:100|unique:biometric_devices,serial_number',
            'location_id' => 'required|exists:locations,id',
            'ip_address' => 'nullable|ip',
            'port' => 'required|integer|min:1|max:65535',
            'device_type' => 'required|string',
            'protocol' => 'required|string',
            'status' => 'required|in:Active,Shutdown,Inactive',
            'remarks' => 'nullable|string',
        ]);

        $validated['company_id'] = $companyId;
        $validated['secret_token'] = Str::random(32);
        $validated['health_status'] = 'Healthy';

        BiometricDevice::create($validated);

        return redirect()->route('biometric-devices.index')->with('success', 'Biometric device registered successfully.');
    }

    public function edit(BiometricDevice $biometricDevice)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($biometricDevice->company_id !== $companyId) {
            abort(404);
        }

        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();
        return view('biometrics.devices.edit', compact('biometricDevice', 'locations'));
    }

    public function update(Request $request, BiometricDevice $biometricDevice)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($biometricDevice->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'device_identifier' => [
                'required', 'string', 'max:100',
                Rule::unique('biometric_devices', 'device_identifier')->where('company_id', $companyId)->ignore($biometricDevice->id),
            ],
            'device_name' => 'required|string|max:150',
            'serial_number' => [
                'required', 'string', 'max:100',
                Rule::unique('biometric_devices', 'serial_number')->ignore($biometricDevice->id),
            ],
            'location_id' => 'required|exists:locations,id',
            'ip_address' => 'nullable|ip',
            'port' => 'required|integer|min:1|max:65535',
            'device_type' => 'required|string',
            'protocol' => 'required|string',
            'status' => 'required|in:Active,Shutdown,Inactive',
            'remarks' => 'nullable|string',
        ]);

        $biometricDevice->update($validated);

        return redirect()->route('biometric-devices.index')->with('success', 'Device updated successfully.');
    }

    /**
     * Raw punch logs monitor.
     */
    public function logs(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = BiometricPunchLog::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['employee', 'device', 'location']);

        if ($request->filled('date')) {
            $query->whereDate('punch_time', $request->date);
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('status')) {
            $query->where('processing_status', $request->status);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('biometric_user_id', 'like', $term)
                  ->orWhereHas('employee', function ($eq) use ($term) {
                      $eq->where('first_name', 'like', $term)
                         ->orWhere('last_name', 'like', $term)
                         ->orWhere('employee_code', 'like', $term);
                  });
            });
        }

        $punchLogs = $query->latest('punch_time')->paginate(25);
        $devices = BiometricDevice::withoutGlobalScopes()->where('company_id', $companyId)->get();

        return view('biometrics.logs', compact('punchLogs', 'devices'));
    }

    /**
     * Trigger manual reprocessing of unmapped punches.
     */
    public function reprocessUnmapped()
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $count = $this->biometricService->reprocessUnmappedPunches($companyId);

        return back()->with('success', "Reprocessed {$count} previously unmapped punch logs successfully.");
    }
}

