<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Mandatory Password Change — Platform Super Admin | Kanvtech SaaS</title>

    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/enterprise-design-system.css') }}">

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #0F172A;
            color: #F8FAFC;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .change-password-card {
            background-color: #FFFFFF;
            color: #0F172A;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
            max-width: 520px;
            width: 100%;
            overflow: hidden;
        }

        .card-header-banner {
            background: linear-gradient(135deg, #1E1B4B 0%, #0F172A 100%);
            padding: 32px 36px;
            color: #FFFFFF;
            border-bottom: 3px solid #6366F1;
        }

        .card-body-content {
            padding: 36px;
        }

        .btn-platform-submit {
            background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
            color: #FFFFFF;
            border: none;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-platform-submit:hover {
            background: linear-gradient(135deg, #4338CA 0%, #3730A3 100%);
            color: #FFFFFF;
            box-shadow: 0 8px 16px -4px rgba(79, 70, 229, 0.4);
        }

        .code-font {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body>
    <div class="change-password-card">
        <!-- Header Banner -->
        <div class="card-header-banner">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #6366F1 0%, #0D9488 100%); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 17px; color: #fff;">
                        K
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size: 15px; letter-spacing: -0.01em;">KANVTECH SaaS</div>
                        <div class="text-white-50" style="font-size: 11px;">Platform Operations Core</div>
                    </div>
                </div>
                <span class="badge bg-warning text-dark px-2.5 py-1.5 fw-semibold" style="font-size: 11px; border-radius: 6px;">
                    <i class="bi bi-shield-lock-fill me-1"></i> Mandatory Action
                </span>
            </div>

            <h4 class="fw-bold text-white mb-1" style="font-size: 20px;">Master Password Reset</h4>
            <p class="text-white-50 mb-0" style="font-size: 13px;">
                You have authenticated with a temporary administrative credential. For security compliance, please establish your permanent master password.
            </p>
        </div>

        <!-- Form Body -->
        <div class="card-body-content">
            <!-- Account Badge -->
            <div class="p-3 mb-4 rounded-3 d-flex align-items-center justify-content-between" style="background-color: #F1F5F9; border: 1px solid #E2E8F0;">
                <div>
                    <div class="text-muted small fw-semibold" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Account Under Protection</div>
                    <div class="fw-bold text-dark code-font" style="font-size: 13.5px;">{{ $user->email }}</div>
                </div>
                <span class="badge bg-primary-subtle text-primary border" style="font-size: 10.5px;">Super Admin</span>
            </div>

            @if(session('warning'))
                <div class="alert alert-warning d-flex align-items-start gap-2 py-2.5 px-3 border-0 mb-3" style="background-color: #FFFBEB; color: #92400E; border-radius: 8px; font-size: 12.5px;">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-0.5"></i>
                    <div>{{ session('warning') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger py-2.5 px-3 border-0 mb-3" style="background-color: #FEF2F2; color: #991B1B; border-radius: 8px; font-size: 12.5px;">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('platform.password.update') }}">
                @csrf

                <div class="mb-3">
                    <label for="current_password" class="form-label fw-semibold small" style="color: #334155;">Current Temporary Password <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key-fill"></i></span>
                        <input type="password" 
                               name="current_password" 
                               id="current_password" 
                               class="form-control border-start-0 @error('current_password') is-invalid @enderror" 
                               required 
                               autofocus
                               placeholder="Enter the temporary password provided">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold small" style="color: #334155;">New Permanent Password <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control border-start-0 @error('password') is-invalid @enderror" 
                               required 
                               placeholder="Minimum 8 characters">
                    </div>
                    <div class="form-text" style="font-size: 11px; color: #64748B;">
                        Must be at least 8 characters long and different from the temporary password.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label fw-semibold small" style="color: #334155;">Confirm New Password <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-check2-circle"></i></span>
                        <input type="password" 
                               name="password_confirmation" 
                               id="password_confirmation" 
                               class="form-control border-start-0" 
                               required 
                               placeholder="Re-type new password to confirm">
                    </div>
                </div>

                <button type="submit" class="btn btn-platform-submit w-100 d-flex align-items-center justify-content-center gap-2 mb-3">
                    <span>Set Permanent Master Password</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <div class="text-center pt-3 border-top">
                <form method="POST" action="{{ route('platform.logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link p-0 text-decoration-none text-muted" style="font-size: 12px;">
                        <i class="bi bi-box-arrow-left me-1"></i> Sign out and cancel reset
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

