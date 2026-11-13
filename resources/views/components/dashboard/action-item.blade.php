@props(['action'])

<article class="action-item action-item--{{ $action['tone'] }}" @if(!empty($action['patient_id'])) data-patient-action @endif>
    <span class="action-item__signal" aria-hidden="true"></span>
    <div class="action-item__content">
        <h3>{{ $action['title'] }}</h3>
        <p class="action-item__patient"><strong>{{ $action['patient_name'] ?? '-' }}</strong><span>HN {{ $action['hn'] ?? '-' }}</span></p>
        @if(($action['type'] ?? null) === 'food_review')
            <div class="action-item__meal-meta">
                @if(!empty($action['meal_label']))<span><i class="fa-solid fa-bowl-food" aria-hidden="true"></i>{{ $action['meal_label'] }}</span>@endif
                @if(!empty($action['meal_date']))<span><i class="fa-regular fa-calendar" aria-hidden="true"></i>{{ $action['meal_date'] }}</span>@endif
                @if(!empty($action['meal_time']))<span><i class="fa-regular fa-clock" aria-hidden="true"></i>{{ $action['meal_time'] }}</span>@endif
            </div>
        @endif
    </div>
    <div class="action-item__aside">
        <span class="action-item__status">{{ $action['action'] }}</span>
        <div class="action-item__actions">
            @if(($action['type'] ?? null) === 'food_review')
                <button
                    type="button"
                    data-modal-open="meal-detail-modal"
                    data-detail-patient="{{ $action['patient_name'] ?? '-' }}"
                    data-detail-hn="{{ $action['hn'] ?? '-' }}"
                    data-detail-meal="{{ $action['meal_label'] ?? '-' }}"
                    data-detail-date="{{ $action['meal_date'] ?? '-' }}"
                    data-detail-time="{{ $action['meal_time'] ?? '-' }}"
                    data-detail-image="{{ $action['image_url'] ?? '' }}"
                    data-detail-foods="{{ $action['food_names'] ?? '-' }}"
                    data-detail-energy="{{ $action['energy'] ?? '-' }}"
                    data-detail-recommendation="{{ $action['recommendation'] ?? '-' }}"
                    data-detail-reference="{{ $action['reference_id'] ?? '-' }}"
                    data-detail-review-url="{{ $action['href'] ?? '' }}"
                    data-detail-reviewable="true"
                    aria-label="ดูรายละเอียดอาหาร: {{ $action['food_names'] ?? 'ไม่ระบุชื่ออาหาร' }}"
                    title="{{ $action['food_names'] ?? 'ไม่ระบุชื่ออาหาร' }}"
                ><span>ดูรายละเอียด</span><span aria-hidden="true">→</span></button>
            @elseif(!empty($action['href']))
                <a href="{{ $action['href'] }}">ดูผู้รับบริการ <span aria-hidden="true">→</span></a>
            @endif
        </div>
    </div>
</article>
