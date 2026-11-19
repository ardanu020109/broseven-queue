@props(['title', 'subtitle' => null])

<div class="page-header">
    <div>
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions) && $actions->isNotEmpty())
        <div class="page-actions">{{ $actions }}</div>
    @endif
</div>
