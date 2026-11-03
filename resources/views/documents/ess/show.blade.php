@extends('layouts.app')

@section('title', $document->title)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="font-monospace small px-2 py-0.5 rounded bg-dark text-white fw-semibold">{{ $document->document_number }}</span>
                <span class="badge bg-success">Official Issued Record</span>
                <span class="badge bg-light text-dark border">{{ $document->category }}</span>
            </div>
            <h4 class="fw-bold mb-0" style="color: #0F172A;">{{ $document->title }}</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('documents.my-documents') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> My Documents
            </a>
            <a href="{{ route('documents.ess.download', $document) }}" class="btn btn-teal btn-sm shadow-sm">
                <i class="bi bi-download me-1"></i> Download PDF
            </a>
            <a href="{{ url('/verify-document/' . $document->verification_code) }}" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="bi bi-shield-check me-1"></i> Verify Authenticity
            </a>
        </div>
    </div>

    <!-- Centered A4 Document Sheet -->
    <div class="d-flex justify-content-center">
        <div class="card card-custom border-0 shadow p-4 p-md-5" style="width: 100%; max-width: 820px; background-color: #ffffff; min-height: 900px;">
            <div class="document-ess-viewport" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; font-size: 14px; line-height: 1.6;">
                {!! $document->content_snapshot_html !!}
            </div>
        </div>
    </div>
</div>
@endsection

