@extends('layouts.app')

@section('title', 'Register Biometric Device — Enterprise HRMS')

@section('content')
<div class="mb-4">
    <a href="{{ route('biometric-devices.index') }}" class="text-decoration-none text-muted" style="font-size: 13.5px;">
        <i class="bi bi-arrow-left me-1"></i> Back to Devices
    </a>
    <h1 class="h3 mt-2 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Register Biometric Terminal</h1>
    <p class="text-muted mb-0" style="font-size: 13.5px;">Add hardware device specifications, endpoint and network credentials.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-body p-4">
                <form action="{{ route('biometric-devices.store') }}" method="POST">
                    @csrf

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Device Identifiers & Placement</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Device Identifier</label>
                            <input type="text" name="device_identifier" class="form-control @error('device_identifier') is-invalid @enderror" value="{{ old('device_identifier') }}" placeholder="e.g. PN-P1-GATE-01" required>
                            @error('device_identifier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Device Name</label>
                            <input type="text" name="device_name" class="form-control @error('device_name') is-invalid @enderror" value="{{ old('device_name') }}" placeholder="e.g. Pune Plant Main Turnstile" required>
                            @error('device_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Hardware Serial Number</label>
                            <input type="text" name="serial_number" class="form-control @error('serial_number') is-invalid @enderror" value="{{ old('serial_number') }}" placeholder="e.g. ZK-BIO-9928172" required>
                            @error('serial_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Plant Site / Location</label>
                            <select name="location_id" class="form-select @error('location_id') is-invalid @enderror" required>
                                <option value="">-- Select Location --</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Technical & Integration Protocol</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Device Type</label>
                            <select name="device_type" class="form-select" required>
                                <option value="Biometric Fingerprint">Biometric Fingerprint</option>
                                <option value="Facial Recognition">Facial Recognition</option>
                                <option value="Iris Scanner">Iris Scanner</option>
                                <option value="RFID / Card Reader">RFID / Card Reader</option>
                                <option value="Multi-Modal">Multi-Modal (Face + Finger + Card)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Integration Protocol</label>
                            <select name="protocol" class="form-select" required>
                                <option value="REST Webhook">REST Webhook (Cloud Push)</option>
                                <option value="ZKTeco Push / ADMS">ZKTeco Push / ADMS</option>
                                <option value="Direct IP Poll">Direct IP Polling (Local Network)</option>
                                <option value="SOAP / XML">SOAP / XML Protocol</option>
                                <option value="Manual CSV Import">Manual Batch CSV Import</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Static IP Address / Endpoint (Optional)</label>
                            <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address') }}" placeholder="e.g. 192.168.1.150">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Port</label>
                            <input type="number" name="port" class="form-control" value="{{ old('port', 4370) }}" required>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Operating Status & Remarks</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Initial Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Active">Active</option>
                                <option value="Shutdown">Shutdown (Site Maintenance)</option>
                                <option value="Inactive">Inactive / Offline</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Remarks / Placement Notes</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Installed at Security Gate 1 for workforce turnstile check-in.">{{ old('remarks') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('biometric-devices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">Save Device</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

