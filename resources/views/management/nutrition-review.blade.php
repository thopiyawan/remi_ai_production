@extends('layouts.dashboard')

@section('title', 'ตรวจสอบและปรับโภชนาการอาหาร')

@section('content')
<div class="dashboard-content nutrition-review" data-nutrition-review>
    <header class="dashboard-header nutrition-review__header">
        <div><h1>ตรวจสอบและปรับโภชนาการอาหาร</h1><p>{{ $review['updated_label'] }}</p></div>
    </header>

    <div class="nutrition-review__workspace">
        <x-ui.card class="review-queue">
            <header class="review-section-heading"><h2>รายการที่ตรวจสอบ</h2><span>{{ $review['pending_count'] }}</span></header>
            <label class="review-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span class="sr-only">ค้นหาผู้ป่วยหรือเมนู</span><input type="search" placeholder="ค้นหาผู้ป่วยหรือเมนู" data-review-search></label>

            <div class="review-queue__items" data-review-queue>
                @foreach ($review['queue'] as $index => $item)
                    <a class="review-queue-item {{ (int) $review['selected_meal_id'] === (int) $item['id'] ? 'is-active' : '' }}" href="{{ route('nutrition-review.index', ['meal_id' => $item['id'], 'page' => $review['pagination']['current_page']]) }}" data-review-item data-search-text="{{ $item['name'] }} มื้อ{{ $item['meal'] }}">
                        <span class="review-queue-item__top"><strong>{{ $item['name'] }}</strong><span class="confidence-pill confidence-pill--{{ $item['score_tone'] }}" aria-label="ความมั่นใจ {{ $item['score'] }} เปอร์เซ็นต์">{{ $item['score'] }}%</span></span>
                        <span class="review-queue-item__meta">
                            <strong class="review-queue-item__meal"><span class="meal-dot meal-dot--{{ $item['meal_tone'] }}" aria-hidden="true"></span>มื้อ{{ $item['meal'] }}</strong>
                            <span class="review-queue-item__trail"><span>{{ $item['time'] ?: '-' }}</span><span class="review-queue-item__status status-pill status-pill--{{ $item['tone'] }}">{{ $item['status'] }}</span></span>
                        </span>
                    </a>
                @endforeach
            </div>

            @if (($review['pagination']['last_page'] ?? 1) > 1)
                <nav class="review-pagination" aria-label="หน้าคิวตรวจอาหาร">
                    @if ($review['pagination']['current_page'] > 1)
                        <a href="{{ route('nutrition-review.index', ['page' => $review['pagination']['current_page'] - 1]) }}">← ก่อนหน้า</a>
                    @else
                        <span></span>
                    @endif
                    <span>{{ $review['pagination']['current_page'] }} / {{ $review['pagination']['last_page'] }}</span>
                    @if ($review['pagination']['current_page'] < $review['pagination']['last_page'])
                        <a href="{{ route('nutrition-review.index', ['page' => $review['pagination']['current_page'] + 1]) }}">ถัดไป →</a>
                    @endif
                </nav>
            @endif

            <div class="food-master">
                <header><div><h3>รายการอาหาร Master</h3><p>ค่าตั้งต้นต่อหน่วยบริโภค</p></div><span>{{ count($review['master_foods']) }}</span></header>
                <label class="food-master__search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" placeholder="ค้นหาอาหารทั้งหมดใน DB"></label>
                @forelse ($review['master_foods'] as $food)
                    <button class="master-food" type="button"><span><strong>{{ $food['name'] }}</strong><small>{{ $food['detail'] }}</small></span><b class="text-{{ $food['tone'] }}">{{ $food['energy'] }} kcal</b></button>
                @empty
                    <p>ขณะนี้ยังไม่มีข้อมูล Master Data</p>
                @endforelse
            </div>
        </x-ui.card>

        <form class="food-review-editor" data-food-review-form data-meal-id="{{ $review['selected_meal_id'] }}">
            <x-ui.card class="ai-review-card">
                <header class="review-section-heading"><h2>ภาพต้นฉบับและผลวิเคราะห์ AI</h2><span class="confidence-pill confidence-pill--{{ $review['analysis']['confidence_tone'] }}">ความมั่นใจ {{ $review['analysis']['confidence'] }}%</span></header>
                <div class="ai-review-card__body">
                    @if ($review['analysis']['image_url'])
                        <button class="meal-preview meal-preview--expandable" type="button" data-modal-open="meal-image-modal" aria-label="ขยายภาพอาหารต้นฉบับ">
                            <img src="{{ $review['analysis']['image_url'] }}" alt="ภาพอาหารต้นฉบับ" loading="lazy">
                            <span>{{ $review['analysis']['date'] ?? '-' }} • {{ $review['analysis']['time'] ?: '-' }}</span>
                            <i class="fa-solid fa-magnifying-glass-plus meal-preview__expand-icon" aria-hidden="true"></i>
                        </button>
                    @else
                        <div class="meal-preview">
                            <strong>ไม่มีภาพอาหาร</strong>
                            <span>{{ $review['analysis']['date'] ?? '-' }} • {{ $review['analysis']['time'] ?: '-' }}</span>
                        </div>
                    @endif
                    <div class="ai-review-fields">
                        <label class="review-field review-field--wide"><span>ชื่ออาหาร<span class="ui-required-mark" aria-hidden="true">*</span><span class="sr-only"> จำเป็น</span></span><input name="dish_name" value="{{ $review['analysis']['dish'] }}" required></label>
                        <label class="review-field"><span>มื้ออาหาร<span class="ui-required-mark" aria-hidden="true">*</span><span class="sr-only"> จำเป็น</span></span><input name="meal_type" value="{{ $review['analysis']['meal'] }}" required></label>
                        <label class="review-field"><span>ปริมาณรวม<span class="ui-required-mark" aria-hidden="true">*</span><span class="sr-only"> จำเป็น</span></span><input name="total_weight" value="{{ $review['analysis']['weight'] }}" required></label>
                        <div class="ai-warning"><strong>ข้อวิเคราะห์ GDM</strong><p>{{ $review['analysis']['warning'] }}</p></div>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="review-foods-card">
                <header class="review-section-heading"><h2>รายการอาหารและสารอาหาร</h2><button type="button" data-add-reviewed-food>＋ เพิ่มรายการอาหาร</button></header>
                <p class="review-draft-notice" data-review-draft-notice hidden><strong>รายการใหม่</strong> กรุณากรอกชื่ออาหาร ปริมาณ และหน่วยให้ครบถ้วนก่อนบันทึก</p>
                <div class="review-food-table-wrap">
                    <table class="review-food-table">
                        <thead><tr><th>รายการ<span class="ui-required-mark" aria-hidden="true">*</span></th><th>ปริมาณ<span class="ui-required-mark" aria-hidden="true">*</span></th><th>พลังงาน</th><th>ความมั่นใจ</th><th></th></tr></thead>
                        <tbody data-reviewed-foods>
                            @foreach ($review['foods'] as $food)
                                <tr data-reviewed-food-row data-food-id="{{ $food['id'] ?? '' }}" data-weight-g="{{ $food['weight_g'] ?? '' }}" data-energy-kcal="{{ $food['energy_kcal'] ?? '' }}" data-carbohydrate-g="{{ data_get($food, 'nutrients.carbohydrate_g') }}" data-protein-g="{{ data_get($food, 'nutrients.protein_g') }}" data-fat-g="{{ data_get($food, 'nutrients.fat_g') }}" data-fiber-g="{{ data_get($food, 'nutrients.fiber_g') }}" data-sugar-g="{{ data_get($food, 'nutrients.sugar_g') }}" data-sodium-mg="{{ data_get($food, 'nutrients.sodium_mg') }}"><td><input name="food_name" value="{{ $food['name'] }}" aria-label="รายการอาหาร" required></td><td><input name="food_amount" value="{{ $food['amount'] }}" aria-label="ปริมาณ" required></td><td>{{ $food['energy'] }}</td><td><strong class="confidence-value confidence-value--{{ $food['tone'] }}">{{ $food['confidence'] }}</strong></td><td><span class="text-info">ข้อมูลจาก API</span></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="nutrition-totals">
                    @foreach ($review['totals'] as $total)<div class="nutrition-total nutrition-total--{{ $total['tone'] }}"><span>{{ $total['label'] }}</span><strong>{{ $total['value'] }}</strong></div>@endforeach
                </div>
            </x-ui.card>

            <x-ui.card class="meal-advice-card">
                <h2>คำแนะนำของมื้ออาหาร</h2>
                <label><span class="sr-only">คำแนะนำของมื้ออาหาร</span><textarea name="recommendation" rows="2">{{ $review['recommendation'] }}</textarea></label>
                <h3>หมายเหตุเพิ่มเติม</h3>
                <label><span class="sr-only">หมายเหตุเพิ่มเติม</span><textarea name="note" rows="2">{{ $review['note'] }}</textarea></label>
            </x-ui.card>

            <x-ui.card class="review-save-card">
                <button class="ui-button" type="submit" data-review-save>บันทึกผลการตรวจสอบ</button>
                <p>กรุณาตรวจสอบข้อมูลอาหารและคำแนะนำก่อนบันทึก</p>
                <span class="review-save-feedback" role="status" aria-live="polite" data-review-feedback></span>
            </x-ui.card>
        </form>
    </div>

    @if ($review['analysis']['image_url'])
        <x-ui.modal id="meal-image-modal" :title="$review['analysis']['dish']" size="wide">
            <figure class="meal-image-modal__figure">
                <img src="{{ $review['analysis']['image_url'] }}" alt="ภาพอาหารต้นฉบับ มื้อ{{ $review['analysis']['meal'] }}" loading="lazy">
                <figcaption>มื้อ{{ $review['analysis']['meal'] }} • {{ $review['analysis']['date'] ?? '-' }} • {{ $review['analysis']['time'] ?: '-' }}</figcaption>
            </figure>
        </x-ui.modal>
    @endif
</div>
@endsection

@section('dashboard-floating-ui')
    <x-dashboard.chat-widget :chat="$chat" />
@endsection
