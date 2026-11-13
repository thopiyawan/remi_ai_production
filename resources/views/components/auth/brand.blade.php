@props(['compact' => false])

<div {{ $attributes->class(['remi-brand', 'remi-brand--compact' => $compact]) }}>
    <img class="remi-brand__mark" src="{{ asset('css/remi.png') }}" alt="">
    <span class="remi-brand__name">REMI</span>
</div>
