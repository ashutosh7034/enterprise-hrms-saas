{{--
  Reusable Enterprise Table Action Dropdown Component
  Standardizes row actions across all master and operational tables
--}}
@props([
    'id' => 'action-' . uniqid(),
    'size' => 'sm'
])

<div class="dropdown d-inline-block">
    <button class="btn btn-light btn-{{ $size }} p-1 px-2 border-0 rounded-2 text-secondary hover-bg"
            type="button"
            id="{{ $id }}"
            data-bs-toggle="dropdown"
            data-bs-boundary="viewport"
            aria-expanded="false"
            title="Actions"
            style="line-height: 1;">
        <i class="bi bi-three-dots-vertical fs-6"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-1"
        aria-labelledby="{{ $id }}"
        style="min-width: 160px; border-radius: 10px; font-size: 12.5px; z-index: 1050;">
        {{ $slot }}
    </ul>
</div>

