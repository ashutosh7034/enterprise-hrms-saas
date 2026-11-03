@extends('layouts.app')

@section('title', 'Document Authenticity Verification')

@section('content')
<div class="d-flex align-items-center justify-content-center min-vh-100 py-5" style="background-color: var(--surface-canvas);">
    <div style="width: 100%; max-width: 620px; padding: 20px;">
        <!-- Header -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-2 shadow-sm" style="width: 56px; height: 56px; background-color: var(--brand-accent); color: white; font-size: 26px;">
                <i class="bi bi-shield-check"></i>
            </div>
            <h3 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Document Verification Portal</h3>
            <p class="text-muted small">Verify official HR letters, certificates, and employment records</p>
        </div>

        <!-- Verification Form Card -->
        <div class="card card-custom p-4 shadow-sm border-0 mb-4">
            <form method="GET" action="{{ route('documents.verify.form') }}">
                <label class="form-label small fw-semibold" style="color: #475569;">Enter Verification Code</label>
                <div class="input-group">
                    <span class="input-group-text bg-white" style="border-color: var(--border-strong);"><i class="bi bi-qr-code text-muted"></i></span>
                    <input type="text" name="code" class="form-control" placeholder="e.g. VE-DOC-8F9B2C1A" value="{{ $code ?? '' }}" required style="border-color: var(--border-strong); text-transform: uppercase;">
                    <button class="btn btn-teal px-4" type="submit">Verify Document</button>
                </div>
                <div class="form-text small mt-2">
                    The 10–14 character alphanumeric verification token printed at the bottom of the official letter or certificate.
                </div>
            </form>
        </div>

        @if(isset($result))
            <!-- Result Card -->
            <div class="card card-custom p-4 shadow-sm border-0">
                @if($result['is_valid'])
                    <div class="d-flex align-items-center gap-3 p-3 rounded mb-4" style="background-color: var(--status-success-bg); border: 1px solid var(--status-success-border);">
                        <i class="bi bi-check-circle-fill text-success fs-3"></i>
                        <div>
                            <div class="fw-bold text-success" style="font-size: 16px;">AUTHENTIC DIGITAL RECORD VERIFIED</div>
                            <div class="small text-muted">{{ $result['message'] }}</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr class="border-bottom">
                                    <td class="text-muted small py-2" width="40%">Document Number</td>
                                    <td class="fw-semibold font-monospace py-2" style="color: #0f172a;">{{ $result['document_number'] }}</td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted small py-2">Document Title</td>
                                    <td class="fw-semibold py-2" style="color: #0f172a;">{{ $result['title'] }}</td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted small py-2">Document Type</td>
                                    <td class="py-2"><span class="badge bg-light text-dark border">{{ $result['document_type'] }}</span></td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted small py-2">Issuing Legal Entity</td>
                                    <td class="fw-semibold py-2" style="color: #0f172a;">{{ $result['company_name'] }}</td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted small py-2">Formal Issue Date</td>
                                    <td class="py-2">{{ $result['issue_date'] }}</td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted small py-2">Recipient Masked Name</td>
                                    <td class="fw-semibold py-2" style="color: #0f172a;">{{ $result['recipient_masked'] }}</td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted small py-2">Verification Code</td>
                                    <td class="font-monospace text-teal fw-bold py-2">{{ $result['verification_code'] }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted small py-2">Verification Timestamp</td>
                                    <td class="text-muted small py-2">{{ $result['verification_timestamp'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-secondary py-2 px-3 small mt-4 mb-0 text-muted d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle"></i>
                        <span>In compliance with privacy policies, personal identification numbers and salary figures are withheld from public verification.</span>
                    </div>
                @else
                    <div class="d-flex align-items-center gap-3 p-3 rounded" style="background-color: var(--status-danger-bg); border: 1px solid var(--status-danger-border);">
                        <i class="bi bi-x-circle-fill text-danger fs-3"></i>
                        <div>
                            <div class="fw-bold text-danger" style="font-size: 16px;">VERIFICATION STATUS: {{ strtoupper($result['status']) }}</div>
                            <div class="small text-muted">{{ $result['message'] }}</div>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        Please check if the code was entered accurately or contact our Human Resources department to clarify document status.
                    </p>
                @endif
            </div>
        @endif

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left"></i> Return to HRMS Login</a>
        </div>
    </div>
</div>
@endsection

