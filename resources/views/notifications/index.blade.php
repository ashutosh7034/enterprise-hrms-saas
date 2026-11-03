@extends('layouts.app')

@section('title', 'My Notifications')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Notifications</h4>
        <div class="text-muted" style="font-size: 13px;">View and manage your personal notification feed, announcements, and compliance alerts.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-check2-all me-1"></i> Mark All as Read
                </button>
            </form>
        @endif
        <a href="{{ route('notifications.preferences') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-sliders me-1"></i> Notification Preferences
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 8px;">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('notifications.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4 col-sm-6">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach(['Onboarding', 'Attendance', 'Shift', 'Leave', 'Payroll', 'Loans', 'Settlement', 'Compliance', 'Security'] as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="btn-group btn-group-sm w-100" role="group">
                    <a href="{{ route('notifications.index', array_merge(request()->except('status'), ['status' => ''])) }}" class="btn {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}" style="{{ !request('status') ? 'background-color: #1E293B; border-color: #1E293B;' : '' }}">All</a>
                    <a href="{{ route('notifications.index', array_merge(request()->except('status'), ['status' => 'unread'])) }}" class="btn {{ request('status') === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }}" style="{{ request('status') === 'unread' ? 'background-color: #1E293B; border-color: #1E293B;' : '' }}">Unread ({{ $unreadCount }})</a>
                    <a href="{{ route('notifications.index', array_merge(request()->except('status'), ['status' => 'read'])) }}" class="btn {{ request('status') === 'read' ? 'btn-primary' : 'btn-outline-secondary' }}" style="{{ request('status') === 'read' ? 'background-color: #1E293B; border-color: #1E293B;' : '' }}">Read</a>
                </div>
            </div>
            <div class="col-md-4 col-sm-12 text-md-end">
                @if(request()->hasAny(['category', 'status']))
                    <a href="{{ route('notifications.index') }}" class="btn btn-link btn-sm text-decoration-none text-muted p-0">Clear Filters</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Notification Feed List -->
<div class="card border-0 shadow-sm" style="border-radius: 8px;">
    <div class="list-group list-group-flush" style="border-radius: 8px;">
        @forelse($notifications as $notif)
            @php
                $data = is_array($notif->data) ? $notif->data : (json_decode($notif->data, true) ?? []);
                $isRead = $notif->read_at !== null;
            @endphp
            <div class="list-group-item p-3 {{ $isRead ? 'bg-white' : 'bg-light' }}" style="transition: background-color 0.15s ease;">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background-color: #F1F5F9; flex-shrink: 0;">
                        <i class="bi {{ $data['icon'] ?? 'bi-bell' }} fs-5" style="color: #0D9488;"></i>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold" style="font-size: 14px; color: #0F172A;">{{ $data['title'] ?? 'Notification' }}</span>
                                <span class="badge" style="background-color: #E2E8F0; color: #475569; font-size: 11px;">{{ $notif->category }}</span>
                                @if(!$isRead)
                                    <span class="badge" style="background-color: #0D9488; color: #FFFFFF; font-size: 10px;">Unread</span>
                                @endif
                            </div>
                            <span class="text-muted" style="font-size: 12px;">{{ $notif->created_at?->diffForHumans() }}</span>
                        </div>

                        <div class="text-secondary mb-2" style="font-size: 13.5px; line-height: 1.5;">
                            {{ $data['body'] ?? '' }}
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <div>
                                @if(!empty($data['action_url']))
                                    <a href="{{ $data['action_url'] }}" class="btn btn-sm btn-outline-teal" style="color: #0D9488; border-color: #0D9488; font-size: 12px; padding: 3px 10px;">
                                        View Details <i class="bi bi-box-arrow-up-right ms-1"></i>
                                    </a>
                                @endif
                            </div>

                            @if(!$isRead)
                                <form method="POST" action="{{ route('notifications.mark-read', $notif->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm text-decoration-none text-muted p-0" style="font-size: 12px;">
                                        <i class="bi bi-check2"></i> Mark as read
                                    </button>
                                </form>
                            @else
                                <span class="text-muted" style="font-size: 11.5px;"><i class="bi bi-check2-all text-success"></i> Read {{ $notif->read_at->format('d M, H:i') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-5 text-center text-muted">
                <i class="bi bi-bell-slash fs-1 text-secondary opacity-50 d-block mb-3"></i>
                <h6 class="fw-semibold text-dark">No Notifications Found</h6>
                <p class="mb-0" style="font-size: 13px;">You have no notifications matching the selected filter criteria.</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="card-footer bg-white p-3 border-0">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection

