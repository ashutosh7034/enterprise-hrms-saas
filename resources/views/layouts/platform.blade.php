<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Kanvtech HRMS SaaS Control Tower') | Kanvtech</title>

    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/enterprise-design-system.css') }}">

    <style>
        :root {
            --platform-sidebar-width: 250px;
            --platform-sidebar-bg: #0B0F19;
            --platform-sidebar-border: rgba(255, 255, 255, 0.08);
            --platform-topbar-height: 62px;
            --platform-topbar-bg: #FFFFFF;
            --platform-topbar-border: #E2E8F0;
            --platform-primary: #4F46E5;
            --platform-accent: #6366F1;
            --platform-teal: #0D9488;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* =========================================================================
           PLATFORM LEFT SIDEBAR (FIXED 250PX, INDEPENDENT SCROLL)
           ========================================================================= */
        .platform-sidebar {
            width: var(--platform-sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            background-color: var(--platform-sidebar-bg);
            border-right: 1px solid var(--platform-sidebar-border);
            z-index: 1040;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-y: auto;
            transition: transform 0.25s ease-in-out;
        }

        .platform-sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .platform-sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }

        /* Sidebar Brand Header */
        .sidebar-brand-header {
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--platform-sidebar-border);
        }

        .platform-brand-badge {
            background: linear-gradient(135deg, #4F46E5 0%, #0D9488 100%);
            width: 38px;
            height: 38px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-weight: 800;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
            flex-shrink: 0;
        }

        .brand-title {
            color: #FFFFFF;
            font-weight: 800;
            font-size: 15px;
            letter-spacing: 0.04em;
            line-height: 1.1;
        }

        .brand-subtitle {
            color: #94A3B8;
            font-size: 10.5px;
            letter-spacing: 0.02em;
            margin-top: 2px;
        }

        /* Sidebar Navigation Menu */
        .sidebar-nav-container {
            padding: 16px 14px;
            flex-grow: 1;
        }

        .sidebar-section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748B;
            padding: 0 8px 8px 8px;
        }

        .sidebar-menu-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav-item {
            margin-bottom: 4px;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            font-size: 13.5px;
            font-weight: 500;
            color: #94A3B8;
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid transparent;
            transition: all 0.15s ease-in-out;
        }

        .sidebar-nav-link i {
            font-size: 16px;
            width: 20px;
            text-align: center;
            color: #94A3B8;
            transition: color 0.15s ease;
        }

        .sidebar-nav-link:hover {
            color: #FFFFFF;
            background-color: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.06);
        }

        .sidebar-nav-link:hover i {
            color: #FFFFFF;
        }

        .sidebar-nav-link.active {
            color: #FFFFFF;
            background-color: #4F46E5;
            border-color: #6366F1;
            font-weight: 600;
            box-shadow: 0 2px 8px -1px rgba(79, 70, 229, 0.45);
        }

        .sidebar-nav-link.active i {
            color: #FFFFFF;
        }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 16px 18px;
            border-top: 1px solid var(--platform-sidebar-border);
            background-color: rgba(0, 0, 0, 0.2);
        }

        .platform-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 3px 9px;
            border-radius: 9999px;
            font-size: 10.5px;
            font-weight: 600;
            background-color: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34D399;
        }

        .platform-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #10B981;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.8);
            display: inline-block;
        }

        /* =========================================================================
           TOPBAR & MAIN WORKSPACE
           ========================================================================= */
        .platform-main-wrapper {
            margin-left: var(--platform-sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.25s ease-in-out;
        }

        .platform-topbar {
            height: var(--platform-topbar-height);
            background-color: var(--platform-topbar-bg);
            border-bottom: 1px solid var(--platform-topbar-border);
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.03);
        }

        .platform-toggle-btn {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            color: #334155;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .platform-toggle-btn:hover {
            background-color: #F1F5F9;
            color: #0F172A;
            border-color: #CBD5E1;
        }

        .platform-profile-trigger {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 8px;
            border-radius: 8px;
            background-color: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            color: inherit;
        }

        .platform-profile-trigger:hover {
            background-color: #F8FAFC;
            border-color: #E2E8F0;
        }

        .platform-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4F46E5 0%, #0D9488 100%);
            color: #FFFFFF;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.25);
            flex-shrink: 0;
        }

        .platform-content-area {
            padding: 24px 28px;
            flex-grow: 1;
        }

        /* Backdrop for Mobile / Tablet drawer */
        .platform-sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(2px);
            z-index: 1035;
        }

        /* =========================================================================
           RESPONSIVE BREAKPOINTS (TABLET & MOBILE)
           ========================================================================= */
        @media (max-width: 991.98px) {
            .platform-sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }

            .platform-sidebar.show {
                transform: translateX(0);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            }

            .platform-sidebar-backdrop.show {
                display: block;
            }

            .platform-main-wrapper {
                margin-left: 0 !important;
            }

            .platform-topbar {
                padding: 0 18px;
            }

            .platform-content-area {
                padding: 20px 18px;
            }
        }

        /* Desktop Collapsed Sidebar State */
        body.sidebar-collapsed .platform-sidebar {
            transform: translateX(-100%);
        }

        body.sidebar-collapsed .platform-main-wrapper {
            margin-left: 0;
        }

        /* Reusable Card & Code Styles */
        .platform-card {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
            transition: all 0.2s ease;
        }

        .platform-card:hover {
            box-shadow: 0 4px 12px rgba(16, 24, 40, 0.08);
        }

        .code-pill {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 4px;
            background-color: #F1F5F9;
            color: #475569;
            border: 1px solid #E2E8F0;
        }
    </style>
    @yield('styles')
</head>
<body>
    @if(Auth::guard('platform')->check())
    @php
        $platformAdminUser = Auth::guard('platform')->user();
    @endphp

    <!-- Fixed Left Sidebar (250px) -->
    <aside class="platform-sidebar" id="platformSidebar">
        <!-- Sidebar Brand Header -->
        <div class="sidebar-brand-header">
            <a href="{{ route('platform.dashboard') }}" class="d-flex align-items-center gap-3 text-decoration-none">
                <div class="platform-brand-badge">K</div>
                <div>
                    <div class="brand-title">KANVTECH</div>
                    <div class="brand-subtitle">Enterprise HRMS SaaS</div>
                </div>
            </a>
            <button type="button" class="btn-close btn-close-white d-lg-none" id="platformSidebarClose" aria-label="Close"></button>
        </div>

        <!-- Sidebar Navigation Menu -->
        <div class="sidebar-nav-container">
            <div class="sidebar-section-title">Platform Control Tower</div>
            <ul class="sidebar-menu-list">
                <li class="sidebar-nav-item">
                    <a href="{{ route('platform.dashboard') }}" class="sidebar-nav-link {{ request()->routeIs('platform.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-nav-item">
                    <a href="{{ route('platform.tenants.index') }}" class="sidebar-nav-link {{ request()->routeIs('platform.tenants.*') ? 'active' : '' }}">
                        <i class="bi bi-buildings"></i>
                        <span>Customer Tenants</span>
                    </a>
                </li>

                <li class="sidebar-nav-item">
                    <a href="{{ route('platform.subscriptions.index') }}" class="sidebar-nav-link {{ request()->routeIs('platform.subscriptions.*') ? 'active' : '' }}">
                        <i class="bi bi-key"></i>
                        <span>Subscriptions</span>
                    </a>
                </li>

                <li class="sidebar-nav-item">
                    <a href="{{ route('platform.plans.index') }}" class="sidebar-nav-link {{ request()->routeIs('platform.plans.*') ? 'active' : '' }}">
                        <i class="bi bi-layers"></i>
                        <span>Commercial Plans</span>
                    </a>
                </li>

                <li class="sidebar-nav-item">
                    <a href="{{ route('platform.invoices.index') }}" class="sidebar-nav-link {{ request()->routeIs('platform.invoices.*') ? 'active' : '' }}">
                        <i class="bi bi-receipt"></i>
                        <span>Invoices & Billing</span>
                    </a>
                </li>

                <li class="sidebar-nav-item">
                    <a href="{{ route('platform.audit-logs.index') }}" class="sidebar-nav-link {{ request()->routeIs('platform.audit-logs.*') ? 'active' : '' }}">
                        <i class="bi bi-shield-check"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Sidebar Footer Status -->
        <div class="sidebar-footer">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="platform-status-pill">
                    <span class="platform-status-dot"></span>
                    <span>Kanvtech Platform Owner</span>
                </span>
            </div>
            <div style="font-size: 10.5px; color: #64748B;">Multi-Tenant Core v3.0</div>
        </div>
    </aside>

    <!-- Sidebar Backdrop for Mobile/Tablet Drawer -->
    <div class="platform-sidebar-backdrop" id="platformSidebarBackdrop"></div>

    <!-- Main Workspace Area -->
    <div class="platform-main-wrapper" id="platformMainWrapper">
        <!-- Compact Topbar -->
        <header class="platform-topbar">
            <!-- Left: Toggle & Title / Breadcrumb -->
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="platform-toggle-btn" id="platformSidebarToggle" aria-label="Toggle Sidebar Navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark d-none d-sm-inline" style="font-size: 14px; letter-spacing: -0.01em;">
                        Kanvtech HRMS SaaS Control Tower
                    </span>
                    <span class="badge bg-light text-muted border d-none d-md-inline" style="font-size: 10.5px;">Platform Super Admin</span>
                </div>
            </div>

            <!-- Right: Status / Notifications & Admin Profile -->
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <!-- Status Badge -->
                <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 d-none d-lg-inline-flex align-items-center gap-1" style="font-size: 11px; border-radius: 6px;">
                    <span class="platform-status-dot"></span>
                    <span>Multi-Tenant Guard: Active</span>
                </span>

                <!-- Notification Bell -->
                <button type="button" class="btn btn-sm btn-light border position-relative" style="border-radius: 8px; width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Platform Security Alerts">
                    <i class="bi bi-bell text-secondary"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle"></span>
                </button>

                <!-- Profile Dropdown -->
                <div class="dropdown">
                    <button type="button" class="platform-profile-trigger" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="platform-avatar">K</div>
                        <div class="text-start d-none d-md-block" style="line-height: 1.25;">
                            <div class="text-dark fw-semibold" style="font-size: 12.5px;">Kanvtech Platform Admin</div>
                            <div style="font-size: 10.5px; color: #64748B;">{{ $platformAdminUser->email }}</div>
                        </div>
                        <i class="bi bi-chevron-down text-secondary small d-none d-md-inline" style="font-size: 11px;"></i>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2 mt-2" style="min-width: 260px; border-radius: 10px;">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold text-dark" style="font-size: 13px;">Kanvtech Platform Admin</div>
                            <div class="text-muted small">{{ $platformAdminUser->email }}</div>
                            <div class="mt-2 d-flex align-items-center justify-content-between">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;">Platform Super Admin</span>
                                <span class="code-pill text-muted" style="font-size: 10px;">IP: {{ request()->ip() }}</span>
                            </div>
                        </li>
                        <li><h6 class="dropdown-header text-uppercase mt-1" style="font-size: 10px;">Platform Shortcuts</h6></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('platform.dashboard') }}" style="font-size: 13px;">
                                <i class="bi bi-speedometer2 text-primary"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('platform.tenants.index') }}" style="font-size: 13px;">
                                <i class="bi bi-buildings text-primary"></i> Customer Tenants Directory
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('platform.audit-logs.index') }}" style="font-size: 13px;">
                                <i class="bi bi-shield-check text-primary"></i> Security & Audit Trail
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li class="px-2">
                            <form method="POST" action="{{ route('platform.logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2 py-2 rounded-2" style="font-size: 13px;">
                                    <i class="bi bi-box-arrow-right"></i> Sign Out of Platform
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>

                <!-- Standalone Sign Out Button -->
                <form method="POST" action="{{ route('platform.logout') }}" class="d-none d-sm-inline m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 py-1 px-2" style="font-size: 12px; border-radius: 7px;" title="Sign Out of Platform">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="platform-content-area">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 border-0 shadow-sm mb-4" role="alert" style="background-color: #ECFDF5; color: #065F46; border-radius: 8px; border-left: 4px solid #10B981 !important;">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <span style="font-size: 13px;">{{ session('success') }}</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 10px;"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show py-2 px-3 border-0 shadow-sm mb-4" role="alert" style="background-color: #FEF2F2; color: #991B1B; border-radius: 8px; border-left: 4px solid #EF4444 !important;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                        <span class="fw-semibold" style="font-size: 13px;">Action Required</span>
                    </div>
                    <ul class="mb-0 ps-4" style="font-size: 12.5px;">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 10px;"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    @else
        <!-- Guest / Login Layout (Pass-through) -->
        <main>
            @yield('content')
        </main>
    @endif

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar Responsive Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('platformSidebar');
            const toggleBtn = document.getElementById('platformSidebarToggle');
            const closeBtn = document.getElementById('platformSidebarClose');
            const backdrop = document.getElementById('platformSidebarBackdrop');

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function () {
                    if (window.innerWidth < 992) {
                        sidebar.classList.toggle('show');
                        if (backdrop) backdrop.classList.toggle('show');
                    } else {
                        document.body.classList.toggle('sidebar-collapsed');
                    }
                });
            }

            if (closeBtn && sidebar) {
                closeBtn.addEventListener('click', function () {
                    sidebar.classList.remove('show');
                    if (backdrop) backdrop.classList.remove('show');
                });
            }

            if (backdrop && sidebar) {
                backdrop.addEventListener('click', function () {
                    sidebar.classList.remove('show');
                    backdrop.classList.remove('show');
                });
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
