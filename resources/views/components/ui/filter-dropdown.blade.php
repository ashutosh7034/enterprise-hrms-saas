{{--
  Reusable Search & Filter Toolbar for Master and Transaction Pages
--}}
@props([
    'action' => '',
    'searchPlaceholder' => 'Search records by code, name...',
    'statusFilter' => true,
    'currentStatus' => request('status', ''),
    'searchValue' => request('search', '')
])

<form method="GET" action="{{ $action }}" class="card card-enterprise mb-4 p-3 bg-white">
    <div class="row g-2 align-items-center">
        <div class="col-md-5 col-sm-12">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 8px 0 0 8px;">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text"
                       name="search"
                       class="form-control border-start-0 ps-0"
                       placeholder="{{ $searchPlaceholder }}"
                       value="{{ $searchValue }}"
                       style="border-radius: 0 8px 8px 0; font-size: 13px;">
            </div>
        </div>

        @if($statusFilter)
        <div class="col-md-3 col-sm-6">
            <select name="status" class="form-select form-select-sm" style="border-radius: 8px; font-size: 13px;">
                <option value="">All Statuses</option>
                <option value="Active" {{ $currentStatus === 'Active' ? 'selected' : '' }}>Active Only</option>
                <option value="Inactive" {{ $currentStatus === 'Inactive' ? 'selected' : '' }}>Inactive Only</option>
            </select>
        </div>
        @endif

        <div class="col-md-4 col-sm-6 d-flex gap-2 justify-content-md-end">
            <button type="submit" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-1 px-3">
                <i class="bi bi-funnel"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ $action }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
            @endif
        </div>
    </div>
</form>

