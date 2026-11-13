@props(['meal', 'patientName' => '-', 'hn' => '-'])

<div class="meal-row-actions">
    <button
        type="button"
        data-modal-open="meal-detail-modal"
        data-detail-patient="{{ $patientName }}"
        data-detail-hn="{{ $hn }}"
        data-detail-meal="มื้อ{{ $meal['meal'] ?? '-' }}"
        data-detail-date="{{ $meal['date'] ?? '-' }}"
        data-detail-time="{{ !empty($meal['time']) ? $meal['time'] . ' น.' : '-' }}"
        data-detail-image="{{ $meal['image_url'] ?? '' }}"
        data-detail-foods="{{ $meal['food'] ?? '-' }}"
        data-detail-energy="{{ $meal['energy'] ?? '-' }}"
        data-detail-recommendation="{{ $meal['recommendation'] ?? '-' }}"
        data-detail-reference="{{ $meal['id'] ?? '-' }}"
        data-detail-review-url="{{ $meal['review_href'] ?? '' }}"
        data-detail-reviewable="{{ !empty($meal['is_pending']) ? 'true' : 'false' }}"
        aria-label="ดูรายละเอียดอาหาร: {{ $meal['food'] ?? 'ไม่ระบุชื่ออาหาร' }}"
        title="{{ $meal['food'] ?? 'ไม่ระบุชื่ออาหาร' }}"
    >ดูรายละเอียด</button>
</div>
