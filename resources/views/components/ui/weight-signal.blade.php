@props([
    'label' => 'ไม่มีข้อมูล',
    'tone' => 'muted',
])

@php
    $icons = [
        'muted' => 'fa-minus',
        'success' => 'fa-check',
        'info' => 'fa-arrow-down',
        'warning' => 'fa-arrow-up',
        'danger' => 'fa-triangle-exclamation',
    ];
@endphp

<span {{ $attributes->class(['weight-signal', 'weight-signal--'.$tone]) }}>
    <i class="fa-solid {{ $icons[$tone] ?? $icons['muted'] }}" aria-hidden="true"></i>
    <span>{{ $label }}</span>
</span>
