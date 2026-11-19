@props(['items' => []])

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}" aria-label="Dashboard"><i class="bi bi-house-door"></i></a>

    @foreach ($items as [$label, $url])
        <span class="sep"><i class="bi bi-chevron-right"></i></span>

        @if ($url)
            <a href="{{ $url }}">{{ $label }}</a>
        @else
            <span class="current">{{ $label }}</span>
        @endif
    @endforeach
</nav>

