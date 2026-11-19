@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Navigasi halaman">
        <span class="pager-info">Menampilkan {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} dari {{ $paginator->total() }}</span>

        <ul class="pager-list">
            @if ($paginator->onFirstPage())
                <li class="disabled" aria-disabled="true"><span><i class="bi bi-chevron-left"></i></span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"><i class="bi bi-chevron-left"></i></a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="disabled"><span>{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya"><i class="bi bi-chevron-right"></i></a></li>
            @else
                <li class="disabled" aria-disabled="true"><span><i class="bi bi-chevron-right"></i></span></li>
            @endif
        </ul>
    </nav>
@endif
