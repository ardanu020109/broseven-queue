@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'bag' => 'default',
    'toggle' => false,
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->getBag($bag)->first($name);
    $showValue = ! in_array($type, ['password', 'file'], true);
@endphp

<div class="form-group">
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}@if ($attributes->has('required'))<span class="required">*</span>@endif
        </label>
    @endif

    <div class="input-wrap">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @if ($showValue) value="{{ old($name, $value) }}" @endif
            {{ $attributes->except('id')->class(['form-control', 'is-invalid' => (bool) $error]) }}
        >

        @if ($toggle)
            <button type="button" class="input-toggle" data-toggle-password="{{ $id }}" aria-label="Tampilkan password">
                <i class="bi bi-eye"></i>
            </button>
        @endif
    </div>

    @if ($error)
        <small class="form-error">{{ $error }}</small>
    @elseif ($hint)
        <small class="form-hint">{{ $hint }}</small>
    @endif
</div>
