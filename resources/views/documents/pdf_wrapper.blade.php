<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $document->title }} - {{ $document->document_number }}</title>
    <style>
        @page {
            margin: 22mm 18mm 22mm 18mm;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.55;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        h2, h3, h4, h5 {
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 8px;
            font-weight: bold;
        }
        h2 { font-size: 16pt; }
        h3 { font-size: 14pt; }
        h4 { font-size: 12pt; }
        p {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-muted { color: #64748b; }
        .text-danger { color: #dc2626; }
        .small { font-size: 9pt; }
        .mt-3 { margin-top: 12px; }
        .mt-4 { margin-top: 18px; }
        .mt-5 { margin-top: 28px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 18px; }
        .my-3 { margin-top: 12px; margin-bottom: 12px; }
        .pt-3 { padding-top: 12px; }
        .border-top { border-top: 1px solid #cbd5e1; }

        .letter-header {
            text-align: center;
            border-bottom: 2px solid #0d9488;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .letter-header h2 {
            color: #0f172a;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .company-meta {
            font-size: 9pt;
            color: #64748b;
            margin-bottom: 0;
        }
        .header-divider {
            border: 0;
            border-top: 2px solid #0d9488;
            margin-top: 8px;
            margin-bottom: 0;
        }

        .doc-meta {
            width: 100%;
            margin-bottom: 15px;
        }
        .doc-meta td {
            font-size: 9.5pt;
            padding: 2px 0;
        }

        table.table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        table.table-bordered, table.table-bordered th, table.table-bordered td {
            border: 1px solid #cbd5e1;
        }
        table.table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: 600;
            text-align: left;
            padding: 6px 10px;
            font-size: 9.5pt;
        }
        table.table td {
            padding: 6px 10px;
            font-size: 9.5pt;
        }
        table.table-sm th, table.table-sm td {
            padding: 5px 8px;
        }

        .signatory-block {
            margin-top: 25px;
            page-break-inside: avoid;
        }

        .verification-footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 8pt;
            color: #64748b;
            page-break-inside: avoid;
        }
        .verification-box {
            background-color: #f8fafc;
            border: 1px dashed #0d9488;
            padding: 6px 12px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="content-body">
        {!! $document->content_snapshot_html !!}
    </div>

    @if(!empty($document->verification_code))
    <div class="verification-footer">
        <div class="verification-box">
            <strong>OFFICIAL DIGITAL DOCUMENT</strong> &bull; Document No: <strong>{{ $document->document_number }}</strong> &bull; Verification Code: <strong style="color: #0d9488;">{{ $document->verification_code }}</strong>
        </div>
        <div style="margin-top: 4px;">
            Verify this document's authenticity online at: <u>{{ url('/verify-document/' . $document->verification_code) }}</u>
        </div>
    </div>
    @endif
</body>
</html>

