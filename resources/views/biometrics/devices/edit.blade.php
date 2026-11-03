@extends('layouts.app')

@section('title', 'Edit Biometric Device — Enterprise HRMS')

@section('content')
<div class="mb-4">
    <a href="{{ route('biometric-devices.index') }}" class="text-decoration-none text-muted" style="font-size: 13.5px;">
        <i class="bi bi-arrow-left me-1"></i> Back to Devices
    </a>
    <h1 class="h3 mt-2 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Edit Device: {{ $biometricDevice->device_name }}</h1>
    <p class="text-muted mb-0" style="font-size: 13.5px;">Update device credentials, operational status, or network parameters.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-body p-4">
                <form action="{{ route('biometric-devices.update', $biometricDevice->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Device Identifiers & Placement</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Device Identifier</label>
                            <input type="text" name="device_identifier" class="form-control" value="{{ old('device_identifier', $biometricDevice->device_identifier) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Device Name</label>
                            <input type="text" name="device_name" class="form-control" value="{{ old('device_name', $biometricDevice->device_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Hardware Serial Number</label>
                            <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number', $biometricDevice->serial_number) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Plant Site / Location</label>
                            <select name="location_id" class="form-select" required>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id', $biometricDevice->location_id) == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Technical & Integration Protocol</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Device Type</label>
                            <select name="device_type" class="form-select" required>
                                @foreach(['Biometric Fingerprint', 'Facial Recognition', 'Iris Scanner', 'RFID / Card Reader', 'Multi-Modal'] as $type)
                                    <option value="{{ $type }}" {{ old('device_type', $biometricDevice->device_type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Integration Protocol</label>
                            <select name="protocol" class="form-select" required>
                                @foreach(['REST Webhook', 'ZKTeco Push / ADMS', 'Direct IP Poll', 'SOAP / XML', 'Manual CSV Import'] as $proto)
                                    <option value="{{ $proto }}" {{ old('protocol', $biometricDevice->protocol) === $proto ? 'selected' : '' }}>{{ $proto }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Static IP Address / Endpoint</label>
                            <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address', $biometricDevice->ip_address) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Port</label>
                            <input type="number" name="port" class="form-control" value="{{ old('port', $biometricDevice->port) }}" required>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Operating Status & Webhook Secret</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Operating Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Active" {{ old('status', $biometricDevice->status) === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Shutdown" {{ old('status', $biometricDevice->status) === 'Shutdown' ? 'selected' : '' }}>Shutdown (Site Maintenance)</option>
                                <option value="Inactive" {{ old('status', $biometricDevice->status) === 'Inactive' ? 'selected' : '' }}>Inactive / Offline</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Secret Token (for Webhook)</label>
                            <input type="text" class="form-control font-monospace bg-light" value="{{ $biometricDevice->secret_token }}" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $biometricDevice->remarks) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('biometric-devices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

