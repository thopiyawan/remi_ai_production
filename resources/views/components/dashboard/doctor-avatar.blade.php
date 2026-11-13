@props(['name' => ''])

@php
    $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = collect($parts)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8'))
        ->take(2)
        ->implode('');
@endphp

<div class="doctor-profile" title="{{ $name }}" aria-label="แพทย์ {{ $name }}">
    <span class="doctor-profile__avatar" aria-hidden="true">{{ $initials ?: 'RE' }}</span>
</div>
