@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
        <div class="pagination-info text-muted small order-2 order-sm-1">
            {!! __('Showing') !!}
            <span class="fw-semibold text-dark">{{ $paginator->firstItem() }}</span>
            {!! __('to') !!}
            <span class="fw-semibold text-dark">{{ $paginator->lastItem() }}</span>
            {!! __('of') !!}
            <span class="fw-semibold text-dark">{{ $paginator->total() }}</span>
            {!! __('results') !!}
        </div>

        <div class="order-1 order-sm-2">
            <ul class="pagination pagination-enterprise mb-0">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                        <span class="page-link" aria-hidden="true">
                            <i class="bi bi-chevron-left" style="font-size: 11px;"></i>
                            <span class="d-none d-md-inline ms-1">Prev</span>
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">
                            <i class="bi bi-chevron-left" style="font-size: 11px;"></i>
                            <span class="d-none d-md-inline ms-1">Prev</span>
                        </a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link dots">{{ $element }}</span></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page">
                                    <span class="page-link">{{ $page }}</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">
                            <span class="d-none d-md-inline me-1">Next</span>
                            <i class="bi bi-chevron-right" style="font-size: 11px;"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                        <span class="page-link" aria-hidden="true">
                            <span class="d-none d-md-inline me-1">Next</span>
                            <i class="bi bi-chevron-right" style="font-size: 11px;"></i>
                        </span>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
@endif

