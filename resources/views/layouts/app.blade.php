<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') | {{ config('app.name', 'Enterprise HRMS') }}</title>

    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/enterprise-design-system.css') }}">

    <!-- Custom Enterprise Design System Styles -->
    <style>
        :root {
            --surface-canvas: #F8F9FA;
            --surface-card: #FFFFFF;
            --surface-subtle: #F1F3F5;
            --surface-hover: #E9ECEF;
            --border-subtle: #E2E8F0;
            --border-strong: #CBD5E1;
            
            --brand-primary: #1E293B;
            --brand-primary-dark: #0F172A;
            --brand-secondary: #475569;
            --brand-accent: #0D9488;
            --brand-accent-hover: #0F766E;

            --status-success-bg: #ECFDF5;
            --status-success-border: #A7F3D0;
            --status-success-text: #065F46;
            --status-success-dot: #10B981;

            --status-warning-bg: #FFFBEB;
            --status-warning-border: #FDE68A;
            --status-warning-text: #92400E;
            --status-warning-dot: #F59E0B;

            --status-danger-bg: #FEF2F2;
            --status-danger-border: #FECDD3;
            --status-danger-text: #9F1239;
            --status-danger-dot: #F43F5E;

            --status-info-bg: #F0FDF4;
            --status-info-border: #E2E8F0;
            --status-info-text: #334155;
            --status-info-dot: #64748B;
        }

        /* Global Custom Scrollbar for Windows & Modern Browsers */
        html {
            scrollbar-width: thin;
            scrollbar-color: #CBD5E1 transparent;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 9999px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--surface-canvas);
            color: #1E293B;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-size: 14px;
        }

        .code-font {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Sidebar Styling */
        #sidebar {
            width: 260px;
            background-color: var(--brand-sidebar-bg, #101828);
            height: 100vh;
            max-height: 100vh;
            color: #94A3B8;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1040;
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-brand {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #FFFFFF;
            flex-shrink: 0;
        }

        .sidebar-brand .logo-box {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #4F46E5 0%, #0D9488 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
            flex-shrink: 0;
        }

        .sidebar-nav {
            padding: 16px 12px;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.16) transparent;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.16);
            border-radius: 9999px;
        }
        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.32);
        }

        .sidebar-footer {
            flex-shrink: 0;
            padding: 14px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background-color: #0B0F19;
            z-index: 10;
        }

        /* Topbar & Profile */
        .user-avatar-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4F46E5 0%, #0D9488 100%);
            color: #FFFFFF;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #FFFFFF;
            box-shadow: 0 2px 4px rgba(16, 24, 40, 0.08);
            flex-shrink: 0;
        }

        .user-avatar-circle.lg {
            width: 44px;
            height: 44px;
            font-size: 16px;
        }

        .profile-trigger-btn {
            background-color: transparent;
            transition: background-color 0.15s ease;
            cursor: pointer;
        }
        .profile-trigger-btn:hover {
            background-color: #F1F5F9;
        }
        .profile-trigger-btn::after {
            display: none !important;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1035;
            transition: opacity 0.25s ease;
        }

        @media (max-width: 991.98px) {
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.show {
                transform: translateX(0);
            }
            #topbar {
                left: 0 !important;
                padding: 0 16px;
            }
            #main-wrapper {
                margin-left: 0 !important;
            }
            .sidebar-backdrop.show {
                display: block;
            }
        }

        .nav-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748B;
            padding: 12px 10px 6px;
            font-weight: 600;
        }

        .nav-item-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 12px;
            color: #94A3B8;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 13.5px;
            transition: all 0.15s ease;
            margin-bottom: 2px;
        }

        .nav-item-link:hover {
            color: #FFFFFF;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .nav-item-link.active {
            color: #FFFFFF;
            background-color: var(--brand-accent);
        }

        .nav-item-link i {
            font-size: 16px;
        }

        /* Collapsible Sidebar Navigation Styles */
        .sidebar-module-item {
            margin-bottom: 4px;
        }

        .sidebar-module-header {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8.5px 12px;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 7px;
            color: #94A3B8;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
            user-select: none;
        }

        .sidebar-module-header:hover {
            color: #FFFFFF;
            background-color: rgba(255, 255, 255, 0.06);
        }

        .sidebar-module-header:focus-visible {
            outline: 2px solid #0D9488;
            outline-offset: 1px;
        }

        .sidebar-module-header .module-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sidebar-module-header .module-icon {
            font-size: 14.5px;
            color: #64748B;
            transition: color 0.2s ease;
            flex-shrink: 0;
        }

        .sidebar-module-header:hover .module-icon,
        .sidebar-module-header.active-parent .module-icon {
            color: #0D9488;
        }

        .sidebar-module-header .chevron-icon {
            font-size: 11px;
            color: #64748B;
            transition: transform 0.25s ease, color 0.2s ease;
            margin-left: auto;
            flex-shrink: 0;
        }

        .sidebar-module-header:not(.collapsed) .chevron-icon,
        .sidebar-module-header[aria-expanded="true"] .chevron-icon {
            transform: rotate(90deg);
            color: #0D9488;
        }

        .sidebar-module-header.active-parent {
            color: #FFFFFF;
            background-color: rgba(13, 148, 136, 0.12);
            border-color: rgba(13, 148, 136, 0.25);
        }

        .sidebar-submodule-container {
            transition: height 0.22s ease;
        }

        .sidebar-submodule-list {
            border-left: 1.5px solid rgba(255, 255, 255, 0.09);
            margin-left: 17px;
            padding-left: 6px;
            margin-top: 3px;
            margin-bottom: 6px;
        }

        .sidebar-submodule-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 11px;
            color: #94A3B8;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 12.5px;
            transition: all 0.15s ease;
            margin-bottom: 2px;
        }

        .sidebar-submodule-link:hover {
            color: #FFFFFF;
            background-color: rgba(255, 255, 255, 0.06);
        }

        .sidebar-submodule-link.active {
            color: #FFFFFF !important;
            background-color: var(--brand-accent, #0D9488) !important;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(13, 148, 136, 0.3);
        }

        .sidebar-submodule-link i {
            font-size: 13.5px;
            opacity: 0.85;
            flex-shrink: 0;
        }

        .sidebar-submodule-link.active i {
            opacity: 1;
        }

        /* Top Navbar */
        #topbar {
            height: 62px;
            background-color: #FFFFFF;
            border-bottom: 1px solid var(--border-subtle);
            position: fixed;
            top: 0;
            right: 0;
            left: 260px;
            z-index: 1030;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
        }

        /* Main Content Wrapper */
        #main-wrapper {
            margin-left: 260px;
            padding-top: 62px;
            min-height: 100vh;
        }

        .content-container {
            padding: 24px 28px;
        }

        /* Cards & Components */
        .card-custom {
            background-color: var(--surface-card);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
            margin-bottom: 20px;
        }

        .card-custom .card-header {
            background-color: #FFFFFF;
            border-bottom: 1px solid var(--border-subtle);
            padding: 16px 20px;
            font-weight: 600;
            font-size: 15px;
            color: #1E293B;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-custom .card-body {
            padding: 20px;
        }

        /* Buttons */
        .btn-brand {
            background-color: var(--brand-primary);
            color: #FFFFFF;
            border: 1px solid var(--brand-primary);
            font-weight: 500;
            padding: 7px 14px;
            font-size: 13.5px;
            border-radius: 6px;
        }

        .btn-brand:hover {
            background-color: var(--brand-primary-dark);
            color: #FFFFFF;
        }

        .btn-accent {
            background-color: var(--brand-accent);
            color: #FFFFFF;
            border: 1px solid var(--brand-accent);
            font-weight: 500;
            padding: 7px 14px;
            font-size: 13.5px;
            border-radius: 6px;
        }

        .btn-accent:hover {
            background-color: var(--brand-accent-hover);
            color: #FFFFFF;
        }

        /* Badges / Status Pills */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid transparent;
        }

        .status-pill .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .status-pill-success {
            background-color: var(--status-success-bg);
            border-color: var(--status-success-border);
            color: var(--status-success-text);
        }
        .status-pill-success .dot { background-color: var(--status-success-dot); }

        .status-pill-warning {
            background-color: var(--status-warning-bg);
            border-color: var(--status-warning-border);
            color: var(--status-warning-text);
        }
        .status-pill-warning .dot { background-color: var(--status-warning-dot); }

        .status-pill-danger {
            background-color: var(--status-danger-bg);
            border-color: var(--status-danger-border);
            color: var(--status-danger-text);
        }
        .status-pill-danger .dot { background-color: var(--status-danger-dot); }

        .status-pill-info {
            background-color: #EFF6FF;
            border-color: #BFDBFE;
            color: #1E40AF;
        }
        .status-pill-info .dot { background-color: #3B82F6; }

        /* Tables */
        .table-custom {
            margin-bottom: 0;
            font-size: 13.5px;
        }

        .table-custom thead th {
            background-color: #F8FAFC;
            color: #475569;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            border-bottom: 1px solid var(--border-subtle);
            padding: 10px 16px;
            vertical-align: middle;
        }

        .table-custom tbody td {
            padding: 13px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #F1F5F9;
            color: #334155;
        }

        .table-custom tbody tr:hover {
            background-color: #F8FAFC;
        }

        /* Company Switcher Pill */
        .company-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background-color: #F1F5F9;
            border: 1px solid var(--border-subtle);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: #1E293B;
        }

        .company-pill .badge-code {
            background-color: var(--brand-accent);
            color: #FFFFFF;
            font-size: 10.5px;
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* Pagination & Table Footer Layout System */
        .pagination-enterprise,
        .pagination {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .pagination-enterprise .page-item,
        .pagination .page-item {
            margin: 0;
        }

        .pagination-enterprise .page-link,
        .pagination .page-link {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 4px 10px;
            font-size: 12.5px;
            font-weight: 500;
            font-family: inherit;
            line-height: 1;
            color: var(--text-secondary, #475467);
            background-color: #FFFFFF;
            border: 1px solid var(--border-subtle, #E2E8F0);
            border-radius: 6px !important;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        }

        .pagination-enterprise .page-link:hover,
        .pagination .page-link:hover {
            color: #1E293B;
            background-color: var(--surface-subtle, #F1F3F5);
            border-color: var(--border-strong, #CBD5E1);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(16, 24, 40, 0.08);
        }

        .pagination-enterprise .page-item.active .page-link,
        .pagination .page-item.active .page-link {
            z-index: 2;
            color: #FFFFFF !important;
            background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%) !important;
            border-color: #4F46E5 !important;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.35);
        }

        .pagination-enterprise .page-item.disabled .page-link,
        .pagination .page-item.disabled .page-link {
            color: #94A3B8 !important;
            background-color: #F8FAFC !important;
            border-color: #E2E8F0 !important;
            opacity: 0.65;
            cursor: not-allowed;
            pointer-events: none;
            box-shadow: none;
            transform: none;
        }

        .pagination-enterprise .page-link.dots,
        .pagination .page-link.dots {
            border-color: transparent !important;
            background-color: transparent !important;
            cursor: default;
            color: #94A3B8;
        }

        .pagination-info {
            font-size: 12.5px;
            color: #64748B;
            letter-spacing: -0.01em;
        }

        /* Fallback protection: Clamp any unstyled SVGs in pagination */
        nav[role="navigation"] svg,
        .pagination svg,
        svg.w-5.h-5 {
            width: 14px !important;
            height: 14px !important;
            max-width: 14px !important;
            max-height: 14px !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }

        /* Card footer and table container alignment */
        .card-footer {
            background-color: #FFFFFF;
            border-top: 1px solid var(--border-subtle, #E2E8F0);
            padding: 12px 20px;
            border-bottom-left-radius: 8px !important;
            border-bottom-right-radius: 8px !important;
        }

        .table-responsive {
            margin-bottom: 0;
        }
    </style>
    @yield('styles')
</head>
<body>
    @auth
    <!-- Sidebar -->
    <aside id="sidebar" style="background-color: #0F172A;">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <div class="logo-box" style="background: linear-gradient(135deg, #0D9488 0%, #3B82F6 100%); font-weight: 700; color: #FFFFFF;">KT</div>
            <div>
                <div style="font-size: 14px; font-weight: 700; line-height: 1.2; color: #FFFFFF; letter-spacing: -0.01em;">KANVTECH HRMS <span class="d-none">ENTERPRISE HRMS</span></div>
                <div style="font-size: 11px; color: #94A3B8;">People & Operations</div>
            </div>
        </a>

        <div class="sidebar-nav" id="sidebar-scroll-container">
            @php
                $isCoreActive = request()->routeIs('dashboard*');
                $isWorkforceActive = request()->routeIs('employees.*');
                $isComplianceActive = request()->routeIs('blacklist.*');
                $isAssetActive = request()->routeIs('assets.*') || request()->routeIs('damage-assessments.*') || request()->routeIs('fine-masters.*');
                $isAttendanceActive = request()->routeIs('attendance.*') || request()->routeIs('shifts.*') || request()->routeIs('biometric*') || request()->routeIs('overtime.*') || request()->routeIs('plant-shutdowns.*') || request()->routeIs('regularization.*');
                $isLeaveActive = request()->routeIs('leaves.*');
                $isPayrollActive = request()->routeIs('payroll.*');
                $isOrgMastersActive = request()->routeIs('companies.*') || request()->routeIs('admin.masters.*') || request()->routeIs('locations.*') || request()->routeIs('departments.*') || request()->routeIs('designations.*') || request()->routeIs('categories.*') || request()->routeIs('contractors.*');
                $isGovActive = request()->routeIs('audit-logs.*');
                $isNotifActive = request()->routeIs('admin.notifications.*');
                $isDocsActive = request()->routeIs('admin.documents.*');
                $isReportsActive = request()->routeIs('admin.reports.*');
                $isMgrTeamOpsActive = request()->routeIs('attendance.*') || request()->routeIs('leaves.approvals.*') || request()->routeIs('regularization.*') || request()->routeIs('overtime.*');
                $isSelfServiceActive = request()->routeIs('leaves.applications.*') || request()->routeIs('payroll.payslips.*') || request()->routeIs('payroll.loans.*') || request()->routeIs('documents.*');
            @endphp

            {{-- 1. CORE WORKSPACE --}}
            <div class="sidebar-module-item">
                <button type="button" 
                        class="sidebar-module-header {{ $isCoreActive ? 'active-parent' : 'collapsed' }}" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#mod-core-workspace" 
                        aria-expanded="{{ $isCoreActive ? 'true' : 'false' }}"
                        aria-controls="mod-core-workspace">
                    <div class="module-header-title">
                        <i class="module-icon bi bi-grid-1x2"></i>
                        <span>Core Workspace</span>
                    </div>
                    <i class="bi bi-chevron-right chevron-icon"></i>
                </button>
                <div id="mod-core-workspace" class="collapse {{ $isCoreActive ? 'show' : '' }} sidebar-submodule-container">
                    <div class="sidebar-submodule-list">
                        @if(auth()->user()->isHoAdmin())
                            <a href="{{ route('dashboard.admin') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('dashboard.admin') ? 'active' : '' }}">
                                <i class="bi bi-speedometer2"></i>
                                <span>HO Admin Hub</span>
                            </a>
                        @endif
                        @if(auth()->user()->isHrExecutive())
                            <a href="{{ route('dashboard.hr') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('dashboard.hr') ? 'active' : '' }}">
                                <i class="bi bi-briefcase"></i>
                                <span>HR Operations Hub</span>
                            </a>
                        @endif
                        @if(auth()->user()->isManager())
                            <a href="{{ route('dashboard.manager') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('dashboard.manager') ? 'active' : '' }}">
                                <i class="bi bi-people"></i>
                                <span>Manager MSS</span>
                            </a>
                        @endif
                        @if(auth()->user()->isEmployee())
                            <a href="{{ route('dashboard.employee') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('dashboard.employee') ? 'active' : '' }}">
                                <i class="bi bi-person-badge"></i>
                                <span>Employee ESS</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- EMPLOYEE SPECIFIC SELF SERVICE --}}
            @if(auth()->user()->isEmployee())
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isSelfServiceActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-emp-self-service" 
                            aria-expanded="{{ $isSelfServiceActive ? 'true' : 'false' }}"
                            aria-controls="mod-emp-self-service">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-person-circle"></i>
                            <span>My Self-Service</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-emp-self-service" class="collapse {{ $isSelfServiceActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('leaves.applications.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('leaves.applications.*') ? 'active' : '' }}">
                                <i class="bi bi-calendar2-range"></i>
                                <span>Apply / My Leaves</span>
                            </a>
                            <a href="{{ route('payroll.payslips.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('payroll.payslips.*') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>My Payslips</span>
                            </a>
                            <a href="{{ route('payroll.loans.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('payroll.loans.*') ? 'active' : '' }}">
                                <i class="bi bi-cash-coin"></i>
                                <span>My Loans &amp; Advances</span>
                            </a>
                            <a href="{{ route('documents.my-documents') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('documents.my-documents') || request()->routeIs('documents.ess.*') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-pdf"></i>
                                <span>My Documents</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- MANAGER SPECIFIC MODULES --}}
            @if(auth()->user()->isManager())
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isMgrTeamOpsActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-mgr-team-ops" 
                            aria-expanded="{{ $isMgrTeamOpsActive ? 'true' : 'false' }}"
                            aria-controls="mod-mgr-team-ops">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-briefcase"></i>
                            <span>Team Operations</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-mgr-team-ops" class="collapse {{ $isMgrTeamOpsActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('attendance.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                                <i class="bi bi-calendar2-check"></i>
                                <span>Team Attendance</span>
                            </a>
                            <a href="{{ route('leaves.approvals.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('leaves.approvals.*') ? 'active' : '' }}">
                                <i class="bi bi-inbox"></i>
                                <span>Team Leave Approvals</span>
                            </a>
                            <a href="{{ route('regularization.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('regularization.*') ? 'active' : '' }}">
                                <i class="bi bi-pencil-square"></i>
                                <span>Regularizations</span>
                            </a>
                            <a href="{{ route('overtime.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('overtime.*') ? 'active' : '' }}">
                                <i class="bi bi-hourglass-split"></i>
                                <span>Overtime Approvals</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isSelfServiceActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-mgr-self-service" 
                            aria-expanded="{{ $isSelfServiceActive ? 'true' : 'false' }}"
                            aria-controls="mod-mgr-self-service">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-person-circle"></i>
                            <span>My Self-Service</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-mgr-self-service" class="collapse {{ $isSelfServiceActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('leaves.applications.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('leaves.applications.*') ? 'active' : '' }}">
                                <i class="bi bi-calendar2-range"></i>
                                <span>Apply / My Leaves</span>
                            </a>
                            <a href="{{ route('payroll.payslips.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('payroll.payslips.*') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>My Payslips</span>
                            </a>
                            <a href="{{ route('documents.my-documents') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('documents.my-documents') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-pdf"></i>
                                <span>My Documents</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isReportsActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-mgr-reports" 
                            aria-expanded="{{ $isReportsActive ? 'true' : 'false' }}"
                            aria-controls="mod-mgr-reports">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-file-earmark-bar-graph"></i>
                            <span>Analytics &amp; Reports</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-mgr-reports" class="collapse {{ $isReportsActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('admin.reports.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-bar-graph"></i>
                                <span>Team Reports Hub</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- HR / HO ADMIN MODULES --}}
            @if(auth()->user()->isHr() || auth()->user()->isHoAdmin())
                {{-- 2. WORKFORCE & ONBOARDING --}}
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isWorkforceActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-workforce-onboarding" 
                            aria-expanded="{{ $isWorkforceActive ? 'true' : 'false' }}"
                            aria-controls="mod-workforce-onboarding">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-person-lines-fill"></i>
                            <span>Workforce &amp; Onboarding</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-workforce-onboarding" class="collapse {{ $isWorkforceActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('employees.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('employees.index') || request()->routeIs('employees.show') || request()->routeIs('employees.edit') ? 'active' : '' }}">
                                <i class="bi bi-people"></i>
                                <span>Workforce Directory</span>
                            </a>
                            <a href="{{ route('employees.create') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('employees.create') ? 'active' : '' }}">
                                <i class="bi bi-person-plus"></i>
                                <span>Onboard Candidate</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 3. COMPLIANCE & SECURITY --}}
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isComplianceActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-compliance-security" 
                            aria-expanded="{{ $isComplianceActive ? 'true' : 'false' }}"
                            aria-controls="mod-compliance-security">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-shield-check"></i>
                            <span>Compliance &amp; Security</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-compliance-security" class="collapse {{ $isComplianceActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('blacklist.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('blacklist.index') || request()->routeIs('blacklist.create') ? 'active' : '' }}">
                                <i class="bi bi-shield-slash"></i>
                                <span>Central Blacklist</span>
                            </a>
                            <a href="{{ route('blacklist.match-reviews') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('blacklist.match-reviews*') ? 'active' : '' }}">
                                <i class="bi bi-fingerprint"></i>
                                <span>Identity Match Queue</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 4. ASSET LIFECYCLE & FINES --}}
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isAssetActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-asset-fines" 
                            aria-expanded="{{ $isAssetActive ? 'true' : 'false' }}"
                            aria-controls="mod-asset-fines">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-laptop"></i>
                            <span>Asset Lifecycle &amp; Fines</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-asset-fines" class="collapse {{ $isAssetActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('assets.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('assets.*') ? 'active' : '' }}">
                                <i class="bi bi-laptop"></i>
                                <span>Asset Master</span>
                            </a>
                            <a href="{{ route('damage-assessments.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('damage-assessments.*') ? 'active' : '' }}">
                                <i class="bi bi-receipt-cutoff"></i>
                                <span>Damage &amp; Fine Recovery</span>
                            </a>
                            <a href="{{ route('fine-masters.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('fine-masters.*') ? 'active' : '' }}">
                                <i class="bi bi-calculator"></i>
                                <span>Fine Policies</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 5. TIME, ATTENDANCE & SHIFTS --}}
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isAttendanceActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-time-attendance" 
                            aria-expanded="{{ $isAttendanceActive ? 'true' : 'false' }}"
                            aria-controls="mod-time-attendance">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-clock-history"></i>
                            <span>Time, Attendance &amp; Shifts</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-time-attendance" class="collapse {{ $isAttendanceActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('attendance.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('attendance.index') || request()->routeIs('attendance.show') ? 'active' : '' }}">
                                <i class="bi bi-calendar2-check"></i>
                                <span>Daily Muster</span>
                            </a>
                            <a href="{{ route('shifts.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('shifts.*') ? 'active' : '' }}">
                                <i class="bi bi-clock"></i>
                                <span>Shift Master</span>
                            </a>
                            <a href="{{ route('shifts.roster') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('shifts.roster*') ? 'active' : '' }}">
                                <i class="bi bi-calendar3-range"></i>
                                <span>Shift Roster</span>
                            </a>
                            <a href="{{ route('biometric-devices.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('biometric-devices.*') ? 'active' : '' }}">
                                <i class="bi bi-cpu"></i>
                                <span>Biometric Devices</span>
                            </a>
                            <a href="{{ route('biometric.logs') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('biometric.logs*') ? 'active' : '' }}">
                                <i class="bi bi-list-columns-reverse"></i>
                                <span>Raw Punch Logs</span>
                            </a>
                            <a href="{{ route('overtime.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('overtime.*') ? 'active' : '' }}">
                                <i class="bi bi-hourglass-split"></i>
                                <span>Overtime Approvals</span>
                            </a>
                            <a href="{{ route('plant-shutdowns.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('plant-shutdowns.*') ? 'active' : '' }}">
                                <i class="bi bi-slash-circle"></i>
                                <span>Plant Shutdowns</span>
                            </a>
                            <a href="{{ route('regularization.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('regularization.*') ? 'active' : '' }}">
                                <i class="bi bi-pencil-square"></i>
                                <span>Regularization</span>
                            </a>
                            <a href="{{ route('attendance.reconciliation') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('attendance.reconciliation*') ? 'active' : '' }}">
                                <i class="bi bi-check2-all"></i>
                                <span>HO Compliance</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 6. LEAVE & HOLIDAY ENGINE --}}
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isLeaveActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-leave-holiday" 
                            aria-expanded="{{ $isLeaveActive ? 'true' : 'false' }}"
                            aria-controls="mod-leave-holiday">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-calendar2-range"></i>
                            <span>Leave &amp; Holiday Engine</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-leave-holiday" class="collapse {{ $isLeaveActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('leaves.applications.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('leaves.applications.*') ? 'active' : '' }}">
                                <i class="bi bi-calendar-plus"></i>
                                <span>Leave Applications</span>
                            </a>
                            <a href="{{ route('leaves.approvals.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('leaves.approvals.*') ? 'active' : '' }}">
                                <i class="bi bi-inbox"></i>
                                <span>Leave Approvals</span>
                            </a>
                            <a href="{{ route('leaves.balances.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('leaves.balances.*') ? 'active' : '' }}">
                                <i class="bi bi-wallet2"></i>
                                <span>Leave Balances</span>
                            </a>
                            <a href="{{ route('leaves.types.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('leaves.types.*') ? 'active' : '' }}">
                                <i class="bi bi-card-checklist"></i>
                                <span>Leave Types</span>
                            </a>
                            <a href="{{ route('leaves.holidays.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('leaves.holidays.*') ? 'active' : '' }}">
                                <i class="bi bi-calendar-event"></i>
                                <span>Holiday Master</span>
                            </a>
                            <a href="{{ route('leaves.sandwich-policies.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('leaves.sandwich-policies.*') ? 'active' : '' }}">
                                <i class="bi bi-layers"></i>
                                <span>Sandwich Policies</span>
                            </a>
                            <a href="{{ route('leaves.compoff.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('leaves.compoff.*') ? 'active' : '' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>Comp-Off Ledger</span>
                            </a>
                            <a href="{{ route('leaves.accrual.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('leaves.accrual.*') ? 'active' : '' }}">
                                <i class="bi bi-arrow-repeat"></i>
                                <span>Accrual &amp; Year-End</span>
                            </a>
                            <a href="{{ route('leaves.period-locks.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('leaves.period-locks.*') ? 'active' : '' }}">
                                <i class="bi bi-lock"></i>
                                <span>Period Closure</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 7. PAYROLL & COMPENSATION --}}
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isPayrollActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-payroll-settlements" 
                            aria-expanded="{{ $isPayrollActive ? 'true' : 'false' }}"
                            aria-controls="mod-payroll-settlements">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-cash-stack"></i>
                            <span>Payroll &amp; Compensation</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-payroll-settlements" class="collapse {{ $isPayrollActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('payroll.runs.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('payroll.runs.*') ? 'active' : '' }}">
                                <i class="bi bi-speedometer2"></i>
                                <span>Control Tower</span>
                            </a>
                            <a href="{{ route('payroll.structures.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('payroll.structures.*') ? 'active' : '' }}">
                                <i class="bi bi-calculator"></i>
                                <span>Salary Structures</span>
                            </a>
                            <a href="{{ route('payroll.payslips.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('payroll.payslips.*') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Payslips Archive</span>
                            </a>
                            <a href="{{ route('payroll.loans.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('payroll.loans.*') ? 'active' : '' }}">
                                <i class="bi bi-cash-stack"></i>
                                <span>Loans &amp; Advances</span>
                            </a>
                            <a href="{{ route('payroll.fnf.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('payroll.fnf.*') ? 'active' : '' }}">
                                <i class="bi bi-door-open"></i>
                                <span>F&amp;F Settlements</span>
                            </a>
                            <a href="{{ route('payroll.reports.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('payroll.reports.*') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-bar-graph"></i>
                                <span>Payroll Reports</span>
                            </a>
                            <a href="{{ route('payroll.configurations.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('payroll.configurations.*') ? 'active' : '' }}">
                                <i class="bi bi-gear-wide-connected"></i>
                                <span>Payroll Settings</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 8. ORGANIZATIONAL MASTERS --}}
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isOrgMastersActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-org-masters" 
                            aria-expanded="{{ $isOrgMastersActive ? 'true' : 'false' }}"
                            aria-controls="mod-org-masters">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-building"></i>
                            <span>Organizational Masters</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-org-masters" class="collapse {{ $isOrgMastersActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            @if(auth()->user()->isHoAdmin())
                                <a href="{{ route('companies.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('companies.*') ? 'active' : '' }}">
                                    <i class="bi bi-buildings"></i>
                                    <span>Companies</span>
                                </a>
                                <a href="{{ route('admin.masters.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.masters.*') ? 'active' : '' }}">
                                    <i class="bi bi-ui-checks-grid"></i>
                                    <span>Dropdown Masters</span>
                                </a>
                            @endif
                            <a href="{{ route('locations.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('locations.*') ? 'active' : '' }}">
                                <i class="bi bi-geo-alt"></i>
                                <span>Locations &amp; Sites</span>
                            </a>
                            <a href="{{ route('departments.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                                <i class="bi bi-diagram-3"></i>
                                <span>Departments</span>
                            </a>
                            <a href="{{ route('designations.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('designations.*') ? 'active' : '' }}">
                                <i class="bi bi-award"></i>
                                <span>Designations</span>
                            </a>
                            <a href="{{ route('categories.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                                <i class="bi bi-tags"></i>
                                <span>Employee Categories</span>
                            </a>
                            <a href="{{ route('contractors.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('contractors.*') ? 'active' : '' }}">
                                <i class="bi bi-briefcase"></i>
                                <span>Contractor Master</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 9. GOVERNANCE & SECURITY --}}
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isGovActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-governance-security" 
                            aria-expanded="{{ $isGovActive ? 'true' : 'false' }}"
                            aria-controls="mod-governance-security">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-shield-lock"></i>
                            <span>Governance &amp; Security</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-governance-security" class="collapse {{ $isGovActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('audit-logs.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                                <i class="bi bi-shield-check"></i>
                                <span>Audit Trail Log</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 10. NOTIFICATION ENGINE --}}
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isNotifActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-notification-engine" 
                            aria-expanded="{{ $isNotifActive ? 'true' : 'false' }}"
                            aria-controls="mod-notification-engine">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-broadcast-pin"></i>
                            <span>Notification Engine</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-notification-engine" class="collapse {{ $isNotifActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('admin.notifications.dashboard') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.notifications.dashboard') ? 'active' : '' }}">
                                <i class="bi bi-broadcast"></i>
                                <span>Control Tower</span>
                            </a>
                            <a href="{{ route('admin.notifications.logs') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.notifications.logs') ? 'active' : '' }}">
                                <i class="bi bi-journal-text"></i>
                                <span>Delivery Logs</span>
                            </a>
                            <a href="{{ route('admin.notifications.templates.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.notifications.templates.*') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-code"></i>
                                <span>Message Templates</span>
                            </a>
                            <a href="{{ route('admin.notifications.channels.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.notifications.channels.*') ? 'active' : '' }}">
                                <i class="bi bi-sliders"></i>
                                <span>Channel Settings</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 11. LETTERS, FORMS & PRINT ENGINE --}}
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isDocsActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-documents-engine" 
                            aria-expanded="{{ $isDocsActive ? 'true' : 'false' }}"
                            aria-controls="mod-documents-engine">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-printer"></i>
                            <span>Letters &amp; Print Engine</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-documents-engine" class="collapse {{ $isDocsActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('admin.documents.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.documents.index') ? 'active' : '' }}">
                                <i class="bi bi-printer"></i>
                                <span>Control Hub</span>
                            </a>
                            <a href="{{ route('admin.documents.generate') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.documents.generate') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-plus"></i>
                                <span>Generate Document</span>
                            </a>
                            <a href="{{ route('admin.documents.approvals') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.documents.approvals') ? 'active' : '' }}">
                                <i class="bi bi-check2-circle"></i>
                                <span>Approval Queue</span>
                            </a>
                            <a href="{{ route('admin.documents.history') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.documents.history') || request()->routeIs('admin.documents.show') ? 'active' : '' }}">
                                <i class="bi bi-archive"></i>
                                <span>Document History</span>
                            </a>
                            <a href="{{ route('admin.documents.templates.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.documents.templates.*') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-ruled"></i>
                                <span>Template Master</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 12. ENTERPRISE ANALYTICS & REPORTS --}}
                <div class="sidebar-module-item">
                    <button type="button" 
                            class="sidebar-module-header {{ $isReportsActive ? 'active-parent' : 'collapsed' }}" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mod-analytics-reports" 
                            aria-expanded="{{ $isReportsActive ? 'true' : 'false' }}"
                            aria-controls="mod-analytics-reports">
                        <div class="module-header-title">
                            <i class="module-icon bi bi-file-earmark-bar-graph"></i>
                            <span>Enterprise Reports</span>
                        </div>
                        <i class="bi bi-chevron-right chevron-icon"></i>
                    </button>
                    <div id="mod-analytics-reports" class="collapse {{ $isReportsActive ? 'show' : '' }} sidebar-submodule-container">
                        <div class="sidebar-submodule-list">
                            <a href="{{ route('admin.reports.index') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.reports.index') || request()->routeIs('admin.reports.show') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-bar-graph"></i>
                                <span>Reports Hub (53 Reports)</span>
                            </a>
                            <a href="{{ route('admin.reports.bank-advice') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.reports.bank-advice') ? 'active' : '' }}">
                                <i class="bi bi-bank"></i>
                                <span>Bank Transfer Advice</span>
                            </a>
                            <a href="{{ route('admin.reports.schedules') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.reports.schedules*') ? 'active' : '' }}">
                                <i class="bi bi-alarm"></i>
                                <span>Scheduled Reports</span>
                            </a>
                            <a href="{{ route('admin.reports.history') }}" class="nav-item-link sidebar-submodule-link {{ request()->routeIs('admin.reports.history') ? 'active' : '' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>Export History</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <script>
            (function () {
                try {
                    var saved = sessionStorage.getItem('hrms_sidebar_scroll_position');
                    if (saved !== null) {
                        var container = document.getElementById('sidebar-scroll-container');
                        if (container) {
                            container.scrollTop = parseInt(saved, 10) || 0;
                        }
                    }
                } catch (e) {}
            })();
        </script>

        <div class="sidebar-footer">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-white fw-semibold" style="font-size: 11.5px; letter-spacing: -0.01em;">Kanvtech HRMS <span class="d-none">Enterprise HRMS</span></span>
                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 9px; padding: 2px 6px;">
                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #10B981; margin-right: 4px;"></span>Live
                </span>
            </div>
            <div class="text-muted" style="font-size: 10.5px; line-height: 1.35;">
                Phase 1–8 Production Suite<br>
                <span class="text-secondary opacity-75">Workforce • Time • Payroll • Reports</span>
            </div>
        </div>
    </aside>

    <!-- Topbar -->
    <header id="topbar">
        <div class="d-flex align-items-center gap-3">
            <!-- Mobile Toggle -->
            <button type="button" class="btn btn-light d-lg-none p-1 px-2 border-0 rounded-2" id="sidebarToggleBtn" aria-label="Toggle navigation">
                <i class="bi bi-list fs-5"></i>
            </button>

            <!-- Active Company Pill & Switcher -->
            @if(isset($activeCompany))
                <div class="dropdown">
                    <button class="company-pill dropdown-toggle border-0" type="button" id="topbarCompanyDropdown" data-bs-toggle="dropdown" aria-expanded="false" {{ !($canSwitchCompany ?? false) ? 'disabled' : '' }} title="{{ ($canSwitchCompany ?? false) ? 'Click to switch between authorized companies' : 'Fixed Organization Scope' }}">
                        <span class="badge-code">{{ $activeCompany->code }}</span>
                        <span class="company-pill-name text-truncate" style="max-width: 180px;">{{ $activeCompany->name }}</span>
                    </button>
                    @if($canSwitchCompany ?? false)
                        <ul class="dropdown-menu shadow-lg border-0 py-1" aria-labelledby="topbarCompanyDropdown" style="min-width: 250px; border-radius: 12px; z-index: 1060;">
                            <li class="dropdown-header d-flex align-items-center justify-content-between text-uppercase py-2 px-3 border-bottom mb-1" style="font-size: 10.5px; letter-spacing: 0.05em; color: #64748B;">
                                <span>Switch Active Company</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 9px; padding: 2px 5px;">Tenant Isolated</span>
                            </li>
                            @foreach($accessibleCompanies as $comp)
                                <li>
                                    <form method="POST" action="{{ route('company.switch') }}" class="m-0 p-0">
                                        @csrf
                                        <input type="hidden" name="company_id" value="{{ $comp->id }}">
                                        <button type="submit" class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 {{ $comp->id === $activeCompany->id ? 'active fw-semibold' : '' }}">
                                            <div class="d-flex align-items-center gap-2">
                                                @if($comp->id === $activeCompany->id)
                                                    <i class="bi bi-check2 text-teal fw-bold"></i>
                                                @else
                                                    <i class="bi bi-building text-muted" style="font-size: 11px;"></i>
                                                @endif
                                                <span style="font-size: 13px;">{{ $comp->name }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-1.5 ms-2">
                                                <span class="badge {{ $comp->id === $activeCompany->id ? 'bg-teal text-white' : 'bg-light text-secondary border' }}" style="font-size: 10px;">{{ $comp->code }}</span>
                                                @if($comp->id === $activeCompany->id)
                                                    <span class="badge bg-light text-dark border" style="font-size: 9px; padding: 2px 4px;">Active</span>
                                                @endif
                                            </div>
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Notification Bell Dropdown -->
            <div class="dropdown" id="notification-bell-wrapper">
                <button class="btn btn-light position-relative border-0 p-2 rounded-circle" type="button" id="notifDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" style="width: 38px; height: 38px; background-color: #F1F5F9;" title="Notifications">
                    <i class="bi bi-bell-fill fs-6" style="color: #475569;"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notif-badge" style="font-size: 10px; padding: 3px 6px;">
                        0
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0" aria-labelledby="notifDropdownBtn" style="width: 350px; border-radius: 12px; overflow: hidden; z-index: 1060;">
                    <div class="d-flex align-items-center justify-content-between p-3 border-bottom bg-light">
                        <span class="fw-bold" style="font-size: 13.5px; color: #0F172A;">Notifications</span>
                        <div class="d-flex align-items-center gap-2">
                            <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 text-decoration-none" style="font-size: 11.5px; color: #0D9488; font-weight: 600;">Mark all read</button>
                            </form>
                            <a href="{{ route('notifications.preferences') }}" class="text-secondary" title="Preferences"><i class="bi bi-gear" style="font-size: 13px;"></i></a>
                        </div>
                    </div>
                    <div id="notif-dropdown-list" style="max-height: 320px; overflow-y: auto;">
                        <div class="p-4 text-center text-muted" style="font-size: 12px;">
                            <div class="spinner-border spinner-border-sm text-secondary mb-2" role="status"></div>
                            <div>Loading notifications...</div>
                        </div>
                    </div>
                    <div class="p-2 border-top text-center bg-light">
                        <a href="{{ route('notifications.index') }}" class="text-decoration-none fw-semibold" style="font-size: 12px; color: #0D9488;">
                            View All Notifications <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Enterprise User Profile Dropdown -->
            <div class="dropdown">
                <button class="profile-trigger-btn dropdown-toggle d-flex align-items-center gap-2 border-0 bg-transparent p-1 rounded-pill" type="button" id="userProfileDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar-circle">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="text-start d-none d-md-block pe-1">
                        <div style="font-weight: 600; font-size: 13px; color: #1E293B; line-height: 1.2;">{{ auth()->user()->name }}</div>
                        <div style="font-size: 11px; color: #64748B;">{{ auth()->user()->role?->label ?? 'User' }}</div>
                    </div>
                    <i class="bi bi-chevron-down text-muted d-none d-md-inline" style="font-size: 11px;"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-xl border-0 p-0" aria-labelledby="userProfileDropdownBtn" style="min-width: 280px; border-radius: 14px; overflow: hidden; z-index: 1060;">
                    <!-- Profile Header Summary -->
                    <div class="p-3 bg-light border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <div class="user-avatar-circle lg">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            <div class="overflow-hidden">
                                <div class="fw-bold text-dark text-truncate" style="font-size: 13.5px;">{{ auth()->user()->name }}</div>
                                <div class="text-muted text-truncate" style="font-size: 11.5px;">{{ auth()->user()->email }}</div>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 9.5px; padding: 2px 6px;">{{ auth()->user()->role?->label ?? 'User' }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 9.5px; padding: 2px 6px;">{{ $activeCompany?->name ?? 'Enterprise' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Menu Navigation Items -->
                    <div class="py-2">
                        <a href="#" class="dropdown-item d-flex align-items-center gap-2 py-2 px-3" data-bs-toggle="modal" data-bs-target="#userProfileModal">
                            <i class="bi bi-person text-secondary fs-6"></i>
                            <span style="font-size: 13px;">My Profile</span>
                        </a>
                        <a href="{{ route('notifications.preferences') }}" class="dropdown-item d-flex align-items-center gap-2 py-2 px-3">
                            <i class="bi bi-bell text-secondary fs-6"></i>
                            <span style="font-size: 13px;">Notification Preferences</span>
                        </a>
                        @if(auth()->user()->isHoAdmin() || auth()->user()->isHrExecutive())
                        <a href="{{ route('subscription.show') }}" class="dropdown-item d-flex align-items-center gap-2 py-2 px-3">
                            <i class="bi bi-key text-secondary fs-6"></i>
                            <span style="font-size: 13px;">Subscription & License</span>
                        </a>
                        <a href="{{ route('audit-logs.index') }}" class="dropdown-item d-flex align-items-center gap-2 py-2 px-3">
                            <i class="bi bi-shield-check text-secondary fs-6"></i>
                            <span style="font-size: 13px;">Audit Trail & Governance</span>
                        </a>
                        @endif
                    </div>

                    <!-- Logout Button -->
                    <div class="p-2 border-top bg-light">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-light w-100 text-danger d-flex align-items-center justify-content-center gap-2 py-1-5 border" style="font-size: 12.5px; font-weight: 600;">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Sign Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>
    @endauth

    <!-- Main Wrapper -->
    <main id="{{ auth()->check() ? 'main-wrapper' : '' }}">
        @auth
        <div class="content-container">
            <!-- Flash Messages -->
            @if(session('subscription_grace_warning'))
                <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm" role="alert" style="background-color: #FFFBEB; color: #92400E; border-left: 4px solid #F59E0B !important;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('subscription_grace_warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert" style="background-color: var(--status-success-bg); color: var(--status-success-text); border-left: 4px solid var(--status-success-dot) !important;">
                    <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert" style="background-color: var(--status-danger-bg); color: var(--status-danger-text); border-left: 4px solid var(--status-danger-dot) !important;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </div>
        <x-ui.profile-modal />
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        @else
            @yield('content')
        @endauth
    </main>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    @auth
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mobile Sidebar Toggle
            const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');

            if (sidebarToggleBtn && sidebar && sidebarBackdrop) {
                sidebarToggleBtn.addEventListener('click', function () {
                    sidebar.classList.toggle('show');
                    sidebarBackdrop.classList.toggle('show');
                });
                sidebarBackdrop.addEventListener('click', function () {
                    sidebar.classList.remove('show');
                    sidebarBackdrop.classList.remove('show');
                });
            }

            // Sidebar Scroll Position & Expansion State Preservation
            (function () {
                const STORAGE_KEY = 'hrms_sidebar_scroll_position';
                const EXPANDED_KEY = 'hrms_sidebar_expanded_groups';
                const sidebarScrollContainer = document.getElementById('sidebar-scroll-container') || document.querySelector('.sidebar-nav');

                function restoreExpandedState() {
                    try {
                        const raw = sessionStorage.getItem(EXPANDED_KEY);
                        if (!raw) return;
                        const expandedIds = JSON.parse(raw);
                        if (Array.isArray(expandedIds)) {
                            expandedIds.forEach(id => {
                                const el = document.getElementById(id);
                                if (el && el.classList.contains('collapse') && !el.classList.contains('show')) {
                                    el.classList.add('show');
                                    const trigger = document.querySelector(`[data-bs-target="#${id}"], [href="#${id}"]`);
                                    if (trigger) {
                                        trigger.setAttribute('aria-expanded', 'true');
                                        trigger.classList.remove('collapsed');
                                    }
                                }
                            });
                        }
                    } catch (e) {}
                }

                function saveExpandedState() {
                    try {
                        if (!sidebarScrollContainer) return;
                        const openCollapses = Array.from(sidebarScrollContainer.querySelectorAll('.collapse.show'))
                            .map(el => el.id)
                            .filter(Boolean);
                        sessionStorage.setItem(EXPANDED_KEY, JSON.stringify(openCollapses));
                    } catch (e) {}
                }

                function saveScrollPosition() {
                    if (sidebarScrollContainer) {
                        sessionStorage.setItem(STORAGE_KEY, sidebarScrollContainer.scrollTop);
                    }
                }

                function restoreScrollPosition() {
                    if (!sidebarScrollContainer) return;
                    restoreExpandedState();
                    const savedPos = sessionStorage.getItem(STORAGE_KEY);
                    if (savedPos !== null) {
                        const pos = parseInt(savedPos, 10);
                        if (!isNaN(pos)) {
                            sidebarScrollContainer.scrollTop = pos;
                        }
                    } else {
                        // Fallback: If no saved position, ensure active route link is visible
                        const activeLink = sidebarScrollContainer.querySelector('.nav-item-link.active');
                        if (activeLink) {
                            activeLink.scrollIntoView({ block: 'nearest' });
                        }
                    }
                }

                if (sidebarScrollContainer) {
                    // Restore on DOM ready and follow up on paint frames
                    restoreScrollPosition();
                    requestAnimationFrame(restoreScrollPosition);
                    setTimeout(restoreScrollPosition, 60);

                    // Track scrolling in real time
                    sidebarScrollContainer.addEventListener('scroll', function () {
                        sessionStorage.setItem(STORAGE_KEY, sidebarScrollContainer.scrollTop);
                    }, { passive: true });

                    // Save position immediately when any link or toggle inside sidebar is clicked
                    sidebarScrollContainer.addEventListener('click', function (e) {
                        const targetLink = e.target.closest('a');
                        if (targetLink) {
                            saveScrollPosition();
                            saveExpandedState();
                        }
                    });

                    // Track collapse toggles if any submenus expand/collapse
                    sidebarScrollContainer.addEventListener('shown.bs.collapse', saveExpandedState);
                    sidebarScrollContainer.addEventListener('hidden.bs.collapse', saveExpandedState);
                }

                // Window-level events for beforeunload and browser back/forward (pageshow)
                window.addEventListener('beforeunload', function () {
                    saveScrollPosition();
                    saveExpandedState();
                });

                window.addEventListener('pageshow', function () {
                    restoreScrollPosition();
                    requestAnimationFrame(restoreScrollPosition);
                });
            })();

            const badge = document.getElementById('notif-badge');
            const list = document.getElementById('notif-dropdown-list');
            const dropdownBtn = document.getElementById('notifDropdownBtn');

            function fetchNotifications() {
                fetch('{{ route("notifications.feed") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    // Update badge
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        badge.classList.remove('d-none');
                    } else {
                        badge.classList.add('d-none');
                    }

                    // Render dropdown items
                    if (!data.notifications || data.notifications.length === 0) {
                        list.innerHTML = `
                            <div class="p-4 text-center text-muted" style="font-size: 12.5px;">
                                <i class="bi bi-bell-slash fs-4 d-block mb-1 text-secondary opacity-50"></i>
                                No notifications yet
                            </div>
                        `;
                        return;
                    }

                    let html = '';
                    data.notifications.forEach(item => {
                        const bgClass = item.is_read ? 'bg-white' : 'bg-light';
                        const dot = item.is_read ? '' : '<span class="badge rounded-pill bg-teal" style="background-color: #0D9488; font-size: 8px;">New</span>';
                        html += `
                            <div class="p-3 border-bottom ${bgClass} position-relative hover-bg">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background-color: #F1F5F9; width: 32px; height: 32px; flex-shrink: 0;">
                                        <i class="bi ${item.icon || 'bi-bell'} text-secondary" style="font-size: 14px;"></i>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="fw-semibold text-truncate" style="font-size: 12.5px; color: #0F172A; max-width: 190px;">${item.title}</span>
                                            ${dot}
                                        </div>
                                        <div class="text-muted text-truncate" style="font-size: 11.5px; line-height: 1.4;">${item.body}</div>
                                        <div class="d-flex align-items-center justify-content-between mt-2 pt-1 border-top" style="font-size: 10.5px; color: #94A3B8;">
                                            <span>${item.time_ago}</span>
                                            ${item.action_url ? `<a href="${item.action_url}" class="text-teal text-decoration-none fw-semibold" style="color: #0D9488;">View</a>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    list.innerHTML = html;
                })
                .catch(err => {
                    console.error('Failed to load notifications feed:', err);
                });
            }

            // Initial load & refresh on dropdown open
            fetchNotifications();
            if (dropdownBtn) {
                dropdownBtn.addEventListener('show.bs.dropdown', fetchNotifications);
            }
        });
    </script>
    @endauth

    @yield('scripts')
    @stack('scripts')
</body>
</html>

