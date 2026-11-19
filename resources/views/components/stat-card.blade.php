@props(['label', 'value', 'icon' => 'bi-bar-chart', 'tone' => 'primary'])

<div {{ $attributes->class(['stat-card', 'tone-' . $tone]) }}>
    <span class="stat-icon"><i class="bi {{ $icon }}"></i></span>
    <div>
        <div class="stat-value">{{ $value }}</div>
        <div class="stat-label">{{ $label }}</div>
    </div>
</div>
