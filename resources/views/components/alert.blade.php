@props(['type' => 'info', 'dismissible' => true])

<div {{ $attributes->class(['alert', 'alert-' . $type]) }} role="alert">
    <div class="alert-body">{{ $slot }}</div>

    @if ($dismissible)
        <button type="button" class="alert-close" data-dismiss="alert" aria-label="Tutup">&times;</button>
    @endif
</div>
