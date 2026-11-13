@props(['id', 'title', 'size' => 'default', 'filter' => false, 'filterPlaceholder' => 'ค้นหาในรายการทั้งหมด'])

<div class="remi-modal remi-modal--{{ $size }}" id="{{ $id }}" data-modal hidden>
    <button class="remi-modal__backdrop" type="button" data-modal-close aria-label="ปิดหน้าต่าง"></button>
    <section class="remi-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" tabindex="-1">
        <header class="remi-modal__header">
            <div>
                <h2 id="{{ $id }}-title">{{ $title }}</h2>
                @isset($subtitle)<p>{{ $subtitle }}</p>@endisset
            </div>
            <button class="remi-modal__close" type="button" data-modal-close aria-label="ปิด">×</button>
        </header>
        @if($filter)
            <div class="remi-modal__filter">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <label class="sr-only" for="{{ $id }}-filter">{{ $filterPlaceholder }}</label>
                <input id="{{ $id }}-filter" type="search" placeholder="{{ $filterPlaceholder }}" data-modal-filter>
                <span data-modal-filter-status aria-live="polite"></span>
            </div>
        @endif
        <div class="remi-modal__body">{{ $slot }}</div>
        @isset($footer)<footer class="remi-modal__footer">{{ $footer }}</footer>@endisset
    </section>
</div>
