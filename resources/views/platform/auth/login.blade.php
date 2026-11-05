<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Platform Control Center Sign In | Kanvtech Enterprise HRMS SaaS</title>

    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/enterprise-design-system.css') }}">

    <style>
        .platform-login-viewport {
            min-height: 100vh;
            display: flex;
            width: 100%;
            background-color: #0F172A;
        }

        .platform-hero-section {
            flex: 0 0 52%;
            max-width: 52%;
            position: relative;
            background-color: #0B132B;
            background-image: 
                radial-gradient(circle at 18% 22%, rgba(99, 102, 241, 0.28) 0%, transparent 45%),
                radial-gradient(circle at 82% 78%, rgba(13, 148, 136, 0.22) 0%, transparent 48%),
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

        .platform-hero-section::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -60px;
            width: 380px;
            height: 380px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.22) 0%, transparent 70%);
            filter: blur(50px);
            pointer-events: none;
        }

        .platform-logo-mark {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #4F46E5 0%, #0D9488 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-weight: 800;
            font-size: 20px;
            box-shadow: 0 8px 24px -4px rgba(79, 70, 229, 0.5);
        }

        .platform-feature-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 16px 20px;
            backdrop-filter: blur(10px);
            margin-bottom: 12px;
        }

        .platform-auth-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 36px;
            background-color: #FFFFFF;
        }

        .platform-auth-container {
            width: 100%;
            max-width: 420px;
        }

        .btn-platform-primary {
            background-color: #0F172A;
            color: #FFFFFF;
            border: 1px solid #0F172A;
            padding: 11px 18px;
            font-weight: 600;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-platform-primary:hover {
            background-color: #1E293B;
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 8px;
            border-radius: 6px;
            background-color: #F1F5F9;
            color: #475569;
            font-size: 11px;
            font-weight: 500;
        }

        @media (max-width: 991.98px) {
            .platform-hero-section {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="platform-login-viewport">
        <!-- Left Branding & Security Hero Panel -->
        <div class="platform-hero-section">
            <div>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="platform-logo-mark">K</div>
                    <div>
                        <div class="fw-bold" style="font-size: 19px; letter-spacing: -0.02em;">KANVTECH</div>
                        <div class="text-muted" style="font-size: 12px; color: #94A3B8 !important;">Multi-Tenant SaaS Platform Operations</div>
                    </div>
                </div>

                <div class="mb-5 mt-5">
                    <span class="badge bg-indigo-subtle text-indigo px-3 py-1 mb-3" style="background-color: rgba(99, 102, 241, 0.2); color: #C7D2FE; font-size: 11px; border-radius: 9999px;">
                        <i class="bi bi-shield-lock-fill me-1"></i> Dedicated Platform Guard
                    </span>
                    <h2 class="fw-bold text-white mb-3" style="font-size: 28px; line-height: 1.25;">
                        SaaS Platform Control Center
                    </h2>
                    <p style="color: #94A3B8; font-size: 14px; line-height: 1.6;">
                        High-assurance administrative environment for Kanvtech to configure tenants, provision client organizations, and oversee multi-tenant HRMS instances.
                    </p>
                </div>

                <div class="mt-4">
                    <div class="platform-feature-card d-flex align-items-center gap-3">
                        <i class="bi bi-diagram-3 fs-5" style="color: #818CF8;"></i>
                        <div>
                            <div class="text-white fw-semibold" style="font-size: 13px;">Complete Guard Isolation</div>
                            <div style="color: #94A3B8; font-size: 11.5px;">Platform users authenticate separately from customer HRMS users.</div>
                        </div>
                    </div>
                    <div class="platform-feature-card d-flex align-items-center gap-3">
                        <i class="bi bi-cpu fs-5" style="color: #2DD4BF;"></i>
                        <div>
                            <div class="text-white fw-semibold" style="font-size: 13px;">High Security Controls</div>
                            <div style="color: #94A3B8; font-size: 11.5px;">Rate limiting, session regeneration, and cryptographic hash verification.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-top" style="border-color: rgba(255, 255, 255, 0.08) !important;">
                <div class="d-flex align-items-center justify-content-between" style="font-size: 11.5px; color: #64748B;">
                    <span>Enterprise HRMS SaaS Core</span>
                    <span>Kanvtech Platform Guard v1.0</span>
                </div>
            </div>
        </div>

        <!-- Right Authentication Form Panel -->
        <div class="platform-auth-section">
            <div class="platform-auth-container">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-2 rounded-3 mb-2" style="background-color: #F8FAFC; border: 1px solid #E2E8F0;">
                        <i class="bi bi-shield-check fs-4" style="color: #4F46E5;"></i>
                    </div>
                    <h3 class="fw-bold mb-1" style="color: #0F172A; font-size: 22px; letter-spacing: -0.02em;">Platform Sign In</h3>
                    <p class="text-muted" style="font-size: 13px;">Enter your Platform Super Admin credentials to access the control center.</p>
                </div>

                @if(session('success'))
                    <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 border-0 mb-3" style="background-color: #ECFDF5; color: #065F46; border-radius: 8px; font-size: 12.5px;">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger py-2 px-3 border-0 mb-3" style="background-color: #FEF2F2; color: #991B1B; border-radius: 8px; font-size: 12.5px;">
                        @foreach($errors->all() as $error)
                            <div><i class="bi bi-exclamation-circle me-1"></i> {{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <!-- Fast 1-Click Demo Login Box -->
                <div class="p-3 mb-4 rounded-3" style="background: #F8FAFC; border: 1.5px dashed #CBD5E1;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.05em;">
                            ⚡ Fast Demo Access:
                        </span>
                        <span class="badge bg-primary-subtle text-primary" style="font-size: 9.5px;">Super Admin</span>
                    </div>
                    <a href="{{ route('platform.fast-login') }}" class="btn btn-dark w-100 d-flex align-items-center justify-content-between py-2 px-3 text-decoration-none shadow-sm" style="font-size: 13px; background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); border-radius: 8px;">
                        <span class="d-flex align-items-center gap-2 text-white">
                            <i class="bi bi-lightning-charge-fill text-warning fs-6"></i>
                            <span class="fw-semibold">1-Click Fast Login as Super Admin</span>
                        </span>
                        <i class="bi bi-arrow-right-short fs-4 text-white"></i>
                    </a>
                    <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top text-muted" style="font-size: 11px;">
                        <span class="font-monospace">ajay@kanvtech.com</span>
                        <button type="button" class="btn btn-link p-0 text-decoration-none text-primary" style="font-size: 11px;" onclick="fillCreds()">
                            Auto-fill fields &darr;
                        </button>
                    </div>
                </div>

                <form method="POST" action="{{ route('platform.login.post') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold" style="font-size: 13px; color: #334155;">Platform Administrator Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted" style="border-color: #CBD5E1;">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" 
                                   class="form-control border-start-0 @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', 'ajay@kanvtech.com') }}" 
                                   required 
                                   autofocus 
                                   placeholder="ajay@kanvtech.com"
                                   style="font-size: 13.5px; border-color: #CBD5E1;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label for="password" class="form-label fw-semibold mb-0" style="font-size: 13px; color: #334155;">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted" style="border-color: #CBD5E1;">
                                <i class="bi bi-key"></i>
                            </span>
                            <input type="password" 
                                   class="form-control border-start-0 @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   value="{{ old('password', 'AdvaitPlatform@2026#Secure') }}"
                                   required 
                                   placeholder="••••••••••••"
                                   style="font-size: 13.5px; border-color: #CBD5E1;">
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" style="cursor: pointer;">
                            <label class="form-check-label text-muted" for="remember" style="font-size: 12.5px; cursor: pointer;">
                                Keep me signed in
                            </label>
                        </div>
                        <span class="security-badge">
                            <i class="bi bi-lock-fill text-success" style="font-size: 10px;"></i>
                            SSL Encrypted
                        </span>
                    </div>

                    <button type="submit" class="btn btn-platform-primary w-100 d-flex align-items-center justify-content-center gap-2 mb-3">
                        <span>Authenticate Platform Session</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <a href="{{ route('login') }}" class="text-decoration-none d-inline-flex align-items-center gap-1 text-muted" style="font-size: 12px;">
                        <i class="bi bi-box-arrow-left"></i>
                        <span>Return to Tenant Customer HRMS Sign In</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        function fillCreds() {
            var emailEl = document.getElementById('email');
            var passEl = document.getElementById('password');
            if (emailEl) emailEl.value = 'ajay@kanvtech.com';
            if (passEl) passEl.value = '';
        }
    </script>
</body>
</html>

