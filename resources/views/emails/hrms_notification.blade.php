<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectText }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #F8F9FA;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1E293B;
            font-size: 14px;
            line-height: 1.6;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 30px auto;
            background: #FFFFFF;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .email-header {
            background-color: #0F172A;
            padding: 24px 30px;
            color: #FFFFFF;
        }
        .header-brand {
            display: inline-block;
            background-color: #0D9488;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 13px;
            padding: 4px 10px;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        .header-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: #FFFFFF;
        }
        .email-body {
            padding: 30px;
        }
        .category-badge {
            display: inline-block;
            background-color: #F1F5F9;
            color: #475569;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        .greeting {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 14px;
            color: #0F172A;
        }
        .message-content {
            color: #334155;
            margin-bottom: 24px;
            white-space: pre-line;
        }
        .btn-action {
            display: inline-block;
            background-color: #0D9488;
            color: #FFFFFF !important;
            text-decoration: none;
            padding: 11px 22px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            margin-top: 10px;
        }
        .email-footer {
            background-color: #F8FAFC;
            border-top: 1px solid #E2E8F0;
            padding: 20px 30px;
            font-size: 12px;
            color: #64748B;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <span class="header-brand">{{ $companyCode ?? 'HRMS' }}</span>
            <h1 class="header-title">{{ $companyName }}</h1>
        </div>

        <div class="email-body">
            @if(!empty($category))
                <div class="category-badge">{{ $category }}</div>
            @endif

            <div class="greeting">Dear {{ $employeeName }},</div>

            <div class="message-content">
                {{ $bodyText }}
            </div>

            @if(!empty($actionUrl))
                <div>
                    <a href="{{ $actionUrl }}" class="btn-action">{{ $actionText ?? 'View Details' }}</a>
                </div>
            @endif
        </div>

        <div class="email-footer">
            <div>This is an automated system notification from {{ $companyName }} HRMS.</div>
            <div style="margin-top: 4px;">Please do not reply directly to this email. For assistance, contact your HR Department.</div>
        </div>
    </div>
</body>
</html>

