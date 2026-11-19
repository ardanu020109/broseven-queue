@props(['status'])

@php
    $enum = $status instanceof \App\Enums\QueueStatus
        ? $status
        : \App\Enums\QueueStatus::tryFrom((string) $status);
@endphp

@if ($enum)
    <span {{ $attributes->class(['badge', 'badge-' . $enum->badge()]) }}>{{ $enum->label() }}</span>
@endif
