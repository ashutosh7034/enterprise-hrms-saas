<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Models\DocumentVerificationLog;
use App\Models\GeneratedDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentVerificationController extends Controller
{
    /**
     * Display the document verification form or lookup by query parameter.
     */
    public function index(Request $request): View
    {
        $code = trim($request->input('code', ''));
        $verificationResult = null;

        if (!empty($code)) {
            $verificationResult = $this->performLookup($code, $request);
        }

        return view('documents.verify', [
            'code' => $code,
            'result' => $verificationResult,
        ]);
    }

    /**
     * Direct link verification endpoint: /verify-document/{code}
     */
    public function verifyDirect(string $code, Request $request): View
    {
        $verificationResult = $this->performLookup(trim($code), $request);

        return view('documents.verify', [
            'code' => $code,
            'result' => $verificationResult,
        ]);
    }

    /**
     * Perform the verification lookup and record security verification audit log.
     */
    protected function performLookup(string $code, Request $request): array
    {
        $ip = $request->ip() ?? '127.0.0.1';
        $userAgent = substr($request->userAgent() ?? '', 0, 500);

        // Find document by verification code across companies (without tenant scope)
        $doc = GeneratedDocument::withoutGlobalScopes()
            ->with(['company', 'employee'])
            ->where('verification_code', $code)
            ->first();

        if (!$doc) {
            DocumentVerificationLog::create([
                'generated_document_id' => null,
                'verification_code_queried' => $code,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'result_status' => 'Invalid',
            ]);

            return [
                'is_valid' => false,
                'status' => 'Invalid',
                'status_badge' => 'danger',
                'message' => 'No official HRMS document was found matching the provided verification code.',
            ];
        }

        // Determine validity based on document status
        $status = $doc->status;
        $resultStatus = 'Valid';
        $badge = 'success';
        $message = 'This is an authentic, verified document issued by the enterprise.';

        if ($status === 'Cancelled') {
            $resultStatus = 'Revoked';
            $badge = 'danger';
            $message = 'This document was previously issued but has since been formally revoked / cancelled.';
        } elseif ($status === 'Rejected') {
            $resultStatus = 'Invalid';
            $badge = 'warning';
            $message = 'This document draft was rejected and is not legally valid.';
        } elseif ($status === 'Pending Approval' || $status === 'Draft') {
            $resultStatus = 'Invalid';
            $badge = 'warning';
            $message = 'This document is pending internal authorization and has not been officially issued.';
        }

        // Log lookup
        DocumentVerificationLog::create([
            'generated_document_id' => $doc->id,
            'verification_code_queried' => $code,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'result_status' => $resultStatus,
        ]);

        return [
            'is_valid' => ($resultStatus === 'Valid'),
            'status' => $resultStatus,
            'status_badge' => $badge,
            'message' => $message,
            'document_number' => $doc->document_number,
            'verification_code' => $doc->verification_code,
            'title' => $doc->title,
            'document_type' => $doc->document_type,
            'company_name' => $doc->company?->name ?? 'Vishal Enterprise',
            'issue_date' => $doc->issued_at ? $doc->issued_at->format('d F Y') : ($doc->created_at ? $doc->created_at->format('d F Y') : 'N/A'),
            'recipient_masked' => $this->maskName($doc->employee?->full_name ?? 'Confidential Recipient'),
            'verification_timestamp' => now()->format('d M Y, h:i A T'),
        ];
    }

    /**
     * Mask name for zero-PII leak policy (e.g. "Sachin Ramesh Tendulkar" -> "S*** R*** T***").
     */
    protected function maskName(string $name): string
    {
        $parts = explode(' ', trim($name));
        $maskedParts = [];

        foreach ($parts as $part) {
            if (empty($part)) continue;
            $first = mb_substr($part, 0, 1);
            $maskedParts[] = $first . '***';
        }

        return implode(' ', $maskedParts);
    }
}

