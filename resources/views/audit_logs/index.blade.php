@extends('layouts.app')

@section('title', 'Audit Trail Log')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Audit Trail & Governance</h4>
        <p class="text-muted small mb-0">Immutable Security & Master Data Change Logs for <strong>{{ $activeCompany?->name }}</strong></p>
    </div>
    <div>
        <span class="badge bg-dark p-2 code-font">
            <i class="bi bi-shield-lock-fill me-1 text-success"></i> IMMUTABLE LOGGING ACTIVE
        </span>
    </div>
</div>

<!-- Filter Bar -->
<div class="card card-custom mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search actor, model or IP..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <option value="CREATE" {{ request('action') === 'CREATE' ? 'selected' : '' }}>CREATE</option>
                    <option value="UPDATE" {{ request('action') === 'UPDATE' ? 'selected' : '' }}>UPDATE</option>
                    <option value="DELETE" {{ request('action') === 'DELETE' ? 'selected' : '' }}>DELETE</option>
                    <option value="COMPANY_SWITCH" {{ request('action') === 'COMPANY_SWITCH' ? 'selected' : '' }}>COMPANY_SWITCH</option>
                    <option value="LOGIN" {{ request('action') === 'LOGIN' ? 'selected' : '' }}>LOGIN</option>
                    <option value="LOGOUT" {{ request('action') === 'LOGOUT' ? 'selected' : '' }}>LOGOUT</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-brand btn-sm w-100">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header">
        <span>Recorded Audit Entries</span>
        <span class="badge bg-secondary">{{ $logs->total() }} Records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp (UTC)</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Target Model</th>
                        <th>IP & Agent</th>
                        <th>Old & New Value Diffs</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="code-font text-muted small">#{{ $log->id }}</td>
                            <td class="code-font text-dark small" style="white-space: nowrap;">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $log->user_name ?? 'System' }}</div>
                                @if($log->user)
                                    <div class="small text-muted">{{ $log->user->email }}</div>
                                @endif
                            </td>
                            <td>
                                @if($log->action === 'CREATE')
                                    <span class="status-pill status-pill-success"><span class="dot"></span>CREATE</span>
                                @elseif($log->action === 'UPDATE')
                                    <span class="status-pill status-pill-warning"><span class="dot"></span>UPDATE</span>
                                @elseif($log->action === 'DELETE')
                                    <span class="status-pill status-pill-danger"><span class="dot"></span>DELETE</span>
                                @elseif($log->action === 'COMPANY_SWITCH')
                                    <span class="status-pill status-pill-info"><span class="dot"></span>SWITCH</span>
                                @else
                                    <span class="status-pill status-pill-info"><span class="dot"></span>{{ $log->action }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border code-font">
                                    {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                </span>
                            </td>
                            <td class="small code-font text-muted">
                                <div>{{ $log->ip_address ?? '127.0.0.1' }}</div>
                                <div style="max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->user_agent }}">
                                    {{ $log->user_agent }}
                                </div>
                            </td>
                            <td class="small" style="max-width: 320px;">
                                @if($log->old_values && count($log->old_values) > 0)
                                    <div class="text-danger small mb-1">
                                        <strong>Old:</strong> <code>{{ json_encode($log->old_values) }}</code>
                                    </div>
                                @endif
                                @if($log->new_values && count($log->new_values) > 0)
                                    <div class="text-success small">
                                        <strong>New:</strong> <code>{{ json_encode($log->new_values) }}</code>
                                    </div>
                                @endif
                                @if(!$log->old_values && !$log->new_values)
                                    <span class="text-muted">No state payload</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No audit logs matching query filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

