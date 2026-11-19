@props(['service'])

<span {{ $attributes->class(['service-icon']) }} style="background: color-mix(in srgb, {{ $service->safeColor() }} 30%, #fff)">
    @if ($service->logoUrl())
        <img src="{{ $service->logoUrl() }}" alt="" class="service-icon-img">
    @else
        <i class="bi {{ $service->icon ?: 'bi-ticket-perforated' }}"></i>
    @endif
</span>
