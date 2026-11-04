@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="d-flex align-items-center justify-content-between gap-3 py-2">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="btn btn-outline-secondary btn-sm disabled" aria-disabled="true" style="opacity: 0.6; cursor: not-allowed;">
                <i class="bi bi-chevron-left me-1" style="font-size: 11px;"></i> Prev
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-chevron-left me-1" style="font-size: 11px;"></i> Prev
            </a>
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-outline-secondary btn-sm">
                Next <i class="bi bi-chevron-right ms-1" style="font-size: 11px;"></i>
            </a>
        @else
            <span class="btn btn-outline-secondary btn-sm disabled" aria-disabled="true" style="opacity: 0.6; cursor: not-allowed;">
                Next <i class="bi bi-chevron-right ms-1" style="font-size: 11px;"></i>
            </span>
        @endif
    </nav>
@endif

