@extends('layouts.app')

@section('title', 'Sign In — Enterprise HRMS')

@section('styles')
<style>
    /* Premium Enterprise Split-Screen Authentication Styles */
    .login-viewport {
        min-height: 100vh;
        display: flex;
        width: 100%;
        background-color: #0F172A;
    }

    /* Left Branding Section (55% desktop) */
    .brand-hero-section {
        flex: 0 0 54%;
        max-width: 54%;
        position: relative;
        background-color: #0B132B;
        background-image: 
            radial-gradient(circle at 18% 22%, rgba(79, 70, 229, 0.28) 0%, transparent 45%),
            radial-gradient(circle at 82% 78%, rgba(15, 157, 138, 0.24) 0%, transparent 48%),
            linear-gradient(rgba(255, 255, 255, 0.025) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.025) 1px, transparent 1px);
        background-size: 100% 100%, 100% 100%, 36px 36px, 36px 36px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 56px 64px;
        color: #FFFFFF;
        overflow: hidden;
    }

    .brand-hero-section::before {
        content: '';
        position: absolute;
        top: -120px;
        right: -80px;
        width: 420px;
        height: 420px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(79, 70, 229, 0.18) 0%, transparent 70%);
        filter: blur(50px);
        pointer-events: none;
    }

    .brand-hero-section::after {
        content: '';
        position: absolute;
        bottom: -100px;
        left: -80px;
        width: 400px;
        height: 400px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(15, 157, 138, 0.18) 0%, transparent 70%);
        filter: blur(50px);
        pointer-events: none;
    }

    .brand-top-bar {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .brand-logo-mark {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: linear-gradient(135deg, #4F46E5 0%, #0F9D8A 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        font-weight: 800;
        font-size: 18px;
        letter-spacing: -0.02em;
        box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.45);
    }

    .brand-meta-title {
        font-size: 19px;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #FFFFFF;
        line-height: 1.2;
    }

    .brand-meta-sub {
        font-size: 12px;
        color: #94A3B8;
        font-weight: 500;
        letter-spacing: 0.02em;
    }

    .brand-content-body {
        position: relative;
        z-index: 2;
        margin: auto 0;
        padding: 36px 0;
    }

    .brand-headline {
        font-size: 40px;
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -0.035em;
        color: #F8FAFC;
        margin-bottom: 18px;
    }

    .brand-headline-gradient {
        background: linear-gradient(135deg, #A5B4FC 0%, #5EEAD4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .brand-subtext {
        font-size: 15.5px;
        line-height: 1.6;
        color: #CBD5E1;
        max-width: 520px;
        margin-bottom: 32px;
        font-weight: 400;
    }

    /* Abstract Workforce Telemetry Card */
    .telemetry-preview-card {
        background: rgba(15, 23, 42, 0.65);
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-radius: 16px;
        padding: 22px 24px;
        max-width: 540px;
        box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.12);
        margin-bottom: 34px;
    }

    .telemetry-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .telemetry-title {
        font-size: 12.5px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #94A3B8;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pulse-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11.5px;
        font-weight: 600;
        color: #34D399;
        background: rgba(52, 211, 153, 0.12);
        padding: 3px 10px;
        border-radius: 9999px;
        border: 1px solid rgba(52, 211, 153, 0.25);
    }

    .pulse-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background-color: #10B981;
        box-shadow: 0 0 8px #10B981;
        animation: pulseAnimation 2s infinite;
    }

    @keyframes pulseAnimation {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.3); opacity: 1; }
        100% { transform: scale(0.95); opacity: 0.8; }
    }

    .telemetry-metrics-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }

    .telemetry-metric-item {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 10px;
        padding: 10px 14px;
    }

    .metric-item-label {
        font-size: 11px;
        color: #94A3B8;
        margin-bottom: 4px;
        font-weight: 500;
    }

    .metric-item-value {
        font-size: 16px;
        font-weight: 700;
        color: #F8FAFC;
        letter-spacing: -0.02em;
    }

    /* 3 Feature Highlights */
    .features-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        max-width: 560px;
    }

    .feature-pill-box {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .feature-icon-circle {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(79, 70, 229, 0.18);
        border: 1px solid rgba(79, 70, 229, 0.35);
        color: #A5B4FC;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }

    .feature-icon-circle.teal {
        background: rgba(15, 157, 138, 0.18);
        border-color: rgba(15, 157, 138, 0.35);
        color: #5EEAD4;
    }

    .feature-icon-circle.indigo {
        background: rgba(99, 102, 241, 0.18);
        border-color: rgba(99, 102, 241, 0.35);
        color: #C7D2FE;
    }

    .feature-text-title {
        font-size: 13px;
        font-weight: 600;
        color: #F1F5F9;
        margin-bottom: 2px;
    }

    .feature-text-desc {
        font-size: 11.5px;
        color: #94A3B8;
        line-height: 1.4;
    }

    .brand-footer-bar {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: #64748B;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        padding-top: 20px;
    }

    /* Right Authentication Section (46% desktop) */
    .auth-panel-section {
        flex: 0 0 46%;
        max-width: 46%;
        background-color: #F8FAFC;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 48px 32px;
        position: relative;
    }

    .auth-card-container {
        width: 100%;
        max-width: 440px;
    }

    .auth-card {
        background: #FFFFFF;
        border-radius: 20px;
        padding: 38px 36px;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06), 0 20px 40px -15px rgba(15, 23, 42, 0.07);
        border: 1px solid #E2E8F0;
    }

    .auth-card-header {
        margin-bottom: 26px;
    }

    .auth-main-heading {
        font-size: 26px;
        font-weight: 700;
        letter-spacing: -0.03em;
        color: #0F172A;
        margin-bottom: 6px;
    }

    .auth-sub-heading {
        font-size: 13.5px;
        color: #64748B;
        margin-bottom: 0;
        font-weight: 400;
    }

    .form-group-custom {
        margin-bottom: 20px;
    }

    .form-label-custom {
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .input-wrapper-relative {
        position: relative;
    }

    .input-field-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94A3B8;
        font-size: 15px;
        pointer-events: none;
        transition: color 0.15s ease;
    }

    .form-control-enterprise {
        width: 100%;
        height: 44px;
        padding: 10px 14px 10px 40px;
        font-size: 13.5px;
        color: #0F172A;
        background-color: #FFFFFF;
        border: 1.5px solid #CBD5E1;
        border-radius: 10px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-family: inherit;
    }

    .form-control-enterprise:focus {
        border-color: #4F46E5;
        box-shadow: 0 0 0 3.5px rgba(79, 70, 229, 0.14);
        outline: none;
        background-color: #FFFFFF;
    }

    .form-control-enterprise:focus + .input-field-icon {
        color: #4F46E5;
    }

    .toggle-password-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: #94A3B8;
        padding: 4px 6px;
        cursor: pointer;
        font-size: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.15s ease;
    }

    .toggle-password-btn:hover {
        color: #334155;
        background-color: #F1F5F9;
    }

    .form-options-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
    }

    .custom-checkbox-container {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .custom-checkbox-input {
        width: 16px;
        height: 16px;
        border-radius: 4px;
        border: 1.5px solid #CBD5E1;
        cursor: pointer;
        accent-color: #4F46E5;
    }

    .custom-checkbox-label {
        font-size: 12.5px;
        color: #475569;
        cursor: pointer;
        user-select: none;
        margin: 0;
    }

    .btn-auth-submit {
        width: 100%;
        height: 46px;
        background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
        border: none;
        border-radius: 10px;
        color: #FFFFFF;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: -0.01em;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.25);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-auth-submit:hover {
        background: linear-gradient(135deg, #334155 0%, #1E293B 100%);
        box-shadow: 0 6px 16px -2px rgba(15, 23, 42, 0.35);
        transform: translateY(-1px);
        color: #FFFFFF;
    }

    .btn-auth-submit:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.2);
    }

    /* Dev Quick-Switch Cards */
    .quick-role-card {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        padding: 9px 12px;
        text-align: left;
        cursor: pointer;
        transition: all 0.15s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }

    .quick-role-card:hover {
        background: #FFFFFF;
        border-color: #4F46E5;
        box-shadow: 0 2px 8px -2px rgba(79, 70, 229, 0.15);
    }

    .quick-role-card.active-role {
        border-color: #4F46E5;
        background: #EEF2FF;
    }

    .quick-role-title {
        font-size: 12px;
        font-weight: 600;
        color: #1E293B;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .quick-role-email {
        font-size: 11px;
        color: #64748B;
        font-family: 'JetBrains Mono', monospace;
    }

    /* Responsive Breakpoints */
    @media (max-width: 1024px) {
        .brand-hero-section {
            padding: 40px;
        }
        .brand-headline {
            font-size: 32px;
        }
        .telemetry-metrics-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 991.98px) {
        .login-viewport {
            flex-direction: column;
        }
        .brand-hero-section {
            flex: 0 0 auto;
            max-width: 100%;
            padding: 32px 24px;
        }
        .telemetry-preview-card,
        .features-row {
            display: none;
        }
        .brand-headline {
            font-size: 26px;
            margin-bottom: 8px;
        }
        .brand-subtext {
            font-size: 13.5px;
            margin-bottom: 0;
        }
        .brand-footer-bar {
            display: none;
        }
        .auth-panel-section {
            flex: 1 0 auto;
            max-width: 100%;
            padding: 36px 20px;
        }
    }

    @media (max-width: 480px) {
        .auth-card {
            padding: 26px 20px;
            border-radius: 16px;
        }
        .auth-main-heading {
            font-size: 22px;
        }
    }
</style>
@endsection

@section('content')
<div class="login-viewport">

    <!-- LEFT SECTION: Enterprise Branding, Vision & Workforce Telemetry (54% Desktop) -->
    <div class="brand-hero-section">
        <!-- Top Identity & Companies -->
        <div class="brand-top-bar">
            <div class="brand-logo-mark">
                HR
            </div>
            <div>
                <div class="brand-meta-title">Enterprise HRMS</div>
                <div class="brand-meta-sub">Vishal Enterprise • Vrishal Eng. Pvt. Ltd.</div>
            </div>
        </div>

        <!-- Center Content & Vision -->
        <div class="brand-content-body">
            <h1 class="brand-headline">
                Smarter Workforce.<br>
                <span class="brand-headline-gradient">Stronger Enterprise.</span>
            </h1>
            <p class="brand-subtext">
                A unified platform to manage people, operations, compliance, payroll, and workforce intelligence across multi-company enterprise units.
            </p>

            <!-- Abstract Visual: Real-Time Workforce Telemetry Widget -->
            <div class="telemetry-preview-card">
                <div class="telemetry-header">
                    <div class="telemetry-title">
                        <i class="bi bi-hdd-network text-teal"></i> Enterprise Fleet Intelligence
                    </div>
                    <div class="pulse-status-badge">
                        <span class="pulse-dot"></span> System Live & Compliant
                    </div>
                </div>
                <div class="telemetry-metrics-grid">
                    <div class="telemetry-metric-item">
                        <div class="metric-item-label">Dual Tenancy</div>
                        <div class="metric-item-value" style="font-size: 13.5px; color: #5EEAD4;">Vishal & Vrishal</div>
                    </div>
                    <div class="telemetry-metric-item">
                        <div class="metric-item-label">Attendance SLA</div>
                        <div class="metric-item-value" style="color: #38BDF8;">99.4%</div>
                    </div>
                    <div class="telemetry-metric-item">
                        <div class="metric-item-label">Audit Posture</div>
                        <div class="metric-item-value" style="font-size: 13.5px; color: #A5B4FC;">Zero Tamper</div>
                    </div>
                </div>
            </div>

            <!-- 3 Feature Highlights -->
            <div class="features-row">
                <div class="feature-pill-box">
                    <div class="feature-icon-circle">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="feature-text-title">Workforce Management</div>
                        <div class="feature-text-desc">Multi-unit tenant orchestration & 360° employee lifecycle.</div>
                    </div>
                </div>

                <div class="feature-pill-box">
                    <div class="feature-icon-circle teal">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <div class="feature-text-title">Enterprise Security</div>
                        <div class="feature-text-desc">Granular RBAC, audit trails & blacklisting deterrence.</div>
                    </div>
                </div>

                <div class="feature-pill-box">
                    <div class="feature-icon-circle indigo">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div>
                        <div class="feature-text-title">HR Intelligence</div>
                        <div class="feature-text-desc">Real-time payroll control tower, automated attendance & reporting.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer / SLA Statement -->
        <div class="brand-footer-bar">
            <span>&copy; {{ date('Y') }} Enterprise HRMS Suite • High-Availability Multi-Tenant</span>
            <span>SOC-2 & ISO 27001 Aligned</span>
        </div>
    </div>

    <!-- RIGHT SECTION: Authentication Card (46% Desktop) -->
    <div class="auth-panel-section">
        <div class="auth-card-container">
            <div class="auth-card">
                <!-- Card Header -->
                <div class="auth-card-header d-flex align-items-start justify-content-between">
                    <div>
                        <h2 class="auth-main-heading">Welcome back</h2>
                        <p class="auth-sub-heading">Sign in to your Enterprise HRMS workspace.</p>
                    </div>
                    <a href="{{ route('platform.fast-login') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1 shadow-sm" style="font-size: 11px; padding: 4px 10px; border-radius: 8px; font-weight: 600;" title="Fast Login as SaaS Platform Super Admin">
                        <i class="bi bi-shield-lock-fill text-primary"></i>
                        <span>Platform Portal &rarr;</span>
                    </a>
                </div>

                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="alert alert-success d-flex align-items-center py-2 px-3 small mb-3 border-0 rounded-3" style="background-color: #ECFDF5; color: #065F46; border-left: 4px solid #10B981 !important;">
                        <i class="bi bi-check-circle-fill me-2 fs-6"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('status'))
                    <div class="alert alert-info d-flex align-items-center py-2 px-3 small mb-3 border-0 rounded-3" style="background-color: #EFF6FF; color: #1E40AF; border-left: 4px solid #3B82F6 !important;">
                        <i class="bi bi-info-circle-fill me-2 fs-6"></i>
                        <div>{{ session('status') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger d-flex align-items-start py-2 px-3 small mb-3 border-0 rounded-3" style="background-color: #FEF2F2; color: #991B1B; border-left: 4px solid #EF4444 !important;">
                        <i class="bi bi-exclamation-octagon-fill me-2 fs-6 flex-shrink-0 mt-1"></i>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif

                <!-- Authentication Form -->
                <form method="POST" action="{{ route('login.post') }}" id="loginForm" autocomplete="on">
                    @csrf

                    <!-- Email Field -->
                    <div class="form-group-custom">
                        <label for="email_input" class="form-label-custom">
                            <span>Email Address</span>
                        </label>
                        <div class="input-wrapper-relative">
                            <i class="bi bi-envelope input-field-icon"></i>
                            <input 
                                type="email" 
                                name="email" 
                                id="email_input" 
                                class="form-control-enterprise @error('email') is-invalid @enderror" 
                                required 
                                autofocus 
                                autocomplete="username"
                                placeholder="name@enterprise.com"
                                value="{{ old('email', 'ho_admin@enterprise.com') }}">
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group-custom">
                        <label for="password_input" class="form-label-custom">
                            <span>Password</span>
                        </label>
                        <div class="input-wrapper-relative">
                            <i class="bi bi-lock input-field-icon"></i>
                            <input 
                                type="password" 
                                name="password" 
                                id="password_input" 
                                class="form-control-enterprise @error('password') is-invalid @enderror" 
                                required 
                                autocomplete="current-password"
                                placeholder="••••••••"
                                value="password">
                            <button 
                                type="button" 
                                id="togglePasswordBtn" 
                                class="toggle-password-btn" 
                                aria-label="Toggle password visibility"
                                tabindex="-1">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Options Row: Remember Me -->
                    <div class="form-options-row">
                        <div class="custom-checkbox-container">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                class="custom-checkbox-input" 
                                id="remember" 
                                checked>
                            <label class="custom-checkbox-label" for="remember">
                                Keep me signed in
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-auth-submit" id="submitBtn">
                        <span id="submitBtnText">Sign In to Workspace</span>
                        <i class="bi bi-arrow-right" id="submitBtnIcon"></i>
                        <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                    </button>
                </form>

                <!-- Dev Quick-Switch Credentials Bar (Development & Testing Environments Only) -->
                @if(!app()->isProduction() && config('app.env') !== 'production')
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.06em;">
                            Quick Switch Role Credentials (Local Dev / Testing Only):
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 9px;">Dev Tool</span>
                    </div>

                    <div class="d-flex flex-column gap-2" id="quickCredsList">
                        <!-- SaaS Platform Super Admin (1-Click Fast Login) -->
                        <a href="{{ route('platform.fast-login') }}" class="quick-role-card text-decoration-none shadow-sm" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); border: 1.5px solid #334155; color: #FFFFFF;">
                            <div class="quick-role-title text-white">
                                <span class="badge" style="background: linear-gradient(135deg, #6366F1 0%, #0D9488 100%); font-size: 9.5px; padding: 3px 6px;">
                                    ⚡ 1-Click Fast Login
                                </span>
                                <span class="text-white fw-bold">Platform Super Admin</span>
                            </div>
                            <span class="quick-role-email text-white-50" style="font-size: 10.5px;">superadmin@advaititsolutions.com &rarr;</span>
                        </a>

                        <button type="button" class="quick-role-card active-role" onclick="setCreds('ho_admin@enterprise.com', 'password', this)">
                            <div class="quick-role-title">
                                <span class="badge bg-dark" style="font-size: 9.5px; padding: 3px 6px;">HO Admin</span>
                                <span>Vikram Singhania</span>
                            </div>
                            <span class="quick-role-email">ho_admin@enterprise.com</span>
                        </button>

                        <button type="button" class="quick-role-card" onclick="setCreds('hr@vishal.com', 'password', this)">
                            <div class="quick-role-title">
                                <span class="badge bg-primary" style="font-size: 9.5px; padding: 3px 6px;">HR Exec</span>
                                <span>Pooja Kulkarni</span>
                            </div>
                            <span class="quick-role-email">hr@vishal.com</span>
                        </button>

                        <button type="button" class="quick-role-card" onclick="setCreds('manager@vishal.com', 'password', this)">
                            <div class="quick-role-title">
                                <span class="badge bg-warning text-dark" style="font-size: 9.5px; padding: 3px 6px;">Manager MSS</span>
                                <span>Rajesh Sharma</span>
                            </div>
                            <span class="quick-role-email">manager@vishal.com</span>
                        </button>

                        <button type="button" class="quick-role-card" onclick="setCreds('employee@vishal.com', 'password', this)">
                            <div class="quick-role-title">
                                <span class="badge bg-success" style="font-size: 9.5px; padding: 3px 6px;">Employee ESS</span>
                                <span>Sachin Jadhav</span>
                            </div>
                            <span class="quick-role-email">employee@vishal.com</span>
                        </button>
                    </div>
                </div>
                @endif
            </div>

            <!-- Micro Copyright in card footer -->
            <div class="text-center mt-3 text-muted" style="font-size: 11.5px;">
                Protected by Enterprise Role-Based Access Control (RBAC) & Audit Logging
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Quick Credentials Populator
    function setCreds(email, pass, element) {
        const emailInput = document.getElementById('email_input');
        const passInput = document.getElementById('password_input');
        
        emailInput.value = email;
        passInput.value = pass;
        
        // Highlight active card
        document.querySelectorAll('.quick-role-card').forEach(card => card.classList.remove('active-role'));
        if (element) {
            element.classList.add('active-role');
        }

        // Trigger change event for any listeners
        emailInput.dispatchEvent(new Event('input', { bubbles: true }));
        passInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Password Visibility Toggle
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password_input');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        if (toggleBtn && passwordInput && toggleIcon) {
            toggleBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                
                if (isPassword) {
                    toggleIcon.classList.remove('bi-eye');
                    toggleIcon.classList.add('bi-eye-slash');
                    toggleBtn.setAttribute('aria-label', 'Hide password');
                } else {
                    toggleIcon.classList.remove('bi-eye-slash');
                    toggleIcon.classList.add('bi-eye');
                    toggleBtn.setAttribute('aria-label', 'Show password');
                }
            });
        }

        // Form Submit Loading State
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const submitBtnText = document.getElementById('submitBtnText');
        const submitBtnIcon = document.getElementById('submitBtnIcon');
        const submitSpinner = document.getElementById('submitSpinner');

        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                if (submitBtnIcon) submitBtnIcon.classList.add('d-none');
                if (submitSpinner) submitSpinner.classList.remove('d-none');
                if (submitBtnText) submitBtnText.textContent = 'Authenticating...';
            });
        }
    });
</script>
@endsection
