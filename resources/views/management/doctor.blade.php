@extends('layouts.dashboard')

@section('title', 'ภาพรวมการดูแล')

@section('content')
<div class="dashboard-content">
    <header class="dashboard-header">
        <div><h1>ภาพรวมการดูแล</h1><p>{{ $overview['updated_label'] }}</p></div>
        <form class="dashboard-search" method="GET" action="{{ route('patients.index') }}">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <label class="sr-only" for="dashboard-patient-search">ค้นหาผู้รับบริการ</label>
            <input id="dashboard-patient-search" name="q" type="search" placeholder="ค้นหาชื่อหรือ HN">
        </form>
    </header>

    <section class="summary-grid" aria-label="ข้อมูลสรุป">
        @foreach ($overview['summary'] as $item)
            <x-ui.card class="summary-card summary-card--{{ $item['tone'] }}" :id="$item['label'] === 'AI วิเคราะห์อาหาร' ? 'food-analysis' : null">
                <div class="summary-card__heading">
                    <span class="summary-card__icon"><i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i></span>
                    <h2>{{ $item['label'] }}</h2>
                </div>
                <div class="summary-card__body">
                    <strong>{{ $item['value'] }}</strong>
                    @isset($item['detail_lines'])
                        <div class="summary-card__details">
                            @foreach ($item['detail_lines'] as $line)<span>{{ $line }}</span>@endforeach
                        </div>
                    @else
                        <span class="summary-card__detail">{{ $item['detail'] }}</span>
                    @endisset
                </div>
                @isset($item['progress'])
                    <div class="summary-card__progress" role="progressbar" aria-label="ตรวจอาหารแล้ว" aria-valuenow="{{ $item['progress'] }}" aria-valuemin="0" aria-valuemax="100">
                        <span style="width: {{ $item['progress'] }}%"></span>
                    </div>
                @endisset
            </x-ui.card>
        @endforeach
    </section>

    <section class="insight-grid">
        <x-ui.card>
            <div class="card-heading"><h2>ผู้รับบริการที่มีกิจกรรม 7 วันล่าสุด</h2><p>จำนวนผู้รับบริการที่มีการเคลื่อนไหว</p></div>
            <div class="activity-chart" aria-label="จำนวนผู้รับบริการที่มีกิจกรรมกับระบบแบบไม่ซ้ำคนในแต่ละวัน">
                <canvas
                    data-chart="activity"
                    data-labels='@json(array_column($overview['activity'], 'day'))'
                    data-values='@json(array_column($overview['activity'], 'value'))'
                ></canvas>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="card-heading"><h2>ภาวะแทรกซ้อนและสัญญาณน้ำหนัก</h2><p>ภาพรวมในกลุ่มของผู้รับบริการ</p></div>
            <div class="weight-overview">
                <div class="weight-donut" aria-label="ผู้รับบริการทั้งหมด {{ number_format($overview['weight_total']) }} ราย">
                    <canvas
                        data-chart="weight"
                        data-labels='@json(array_column($overview['weight_signals'], 'label'))'
                        data-values='@json(array_column($overview['weight_signals'], 'value'))'
                        data-colors='@json(array_column($overview['weight_signals'], 'color'))'
                        data-total="{{ $overview['weight_total'] }}"
                    ></canvas>
                </div>
                <ul class="weight-legend">
                    @foreach ($overview['weight_signals'] as $signal)
                        <li><i style="background: {{ $signal['color'] }}"></i><span>{{ $signal['label'] }}</span><strong>{{ $signal['value'] }}%</strong></li>
                    @endforeach
                </ul>
            </div>
        </x-ui.card>

        <x-ui.card id="today-actions">
            <div class="card-heading card-heading--action"><h2>ต้องจัดการวันนี้</h2>@if(count($overview['actions']) > 5)<button class="ui-text-button" type="button" data-modal-open="dashboard-actions-modal">ดูทั้งหมด ({{ count($overview['actions']) }}) →</button>@endif</div>
            <div class="action-list">
                @foreach (array_slice($overview['actions'], 0, 6) as $action)
                    <x-dashboard.action-item :action="$action" />
                @endforeach
            </div>
        </x-ui.card>
    </section>

    <x-ui.card id="follow-up" class="follow-up-card">
        <div class="follow-up-card__heading"><h2>ผู้รับบริการที่ควรติดตาม</h2>@if(count($overview['follow_up']) > 5)<button class="ui-text-button" type="button" data-modal-open="dashboard-follow-up-modal">ดูทั้งหมด ({{ count($overview['follow_up']) }}) →</button>@else<a href="{{ route('patients.index') }}">ดูรายชื่อทั้งหมด →</a>@endif</div>
        <div class="follow-up-table-wrap">
            <table class="follow-up-table">
                <thead><tr><th>ผู้รับบริการ</th><th>อายุครรภ์</th><th>สถานะ</th><th>สัญญาณน้ำหนัก</th><th>อัปเดตล่าสุด</th></tr></thead>
                <tbody>
                    @foreach (array_slice($overview['follow_up'], 0, 5) as $person)
                        <tr>
                            <td><a class="patient patient-context-link" href="{{ route('patients.show', $person['id']) }}"><span>{{ mb_substr($person['name'], 0, 1, 'UTF-8') }}</span><div><strong>{{ $person['name'] }}</strong><small>HN {{ $person['hn'] }}</small></div></a></td>
                            <td><strong>{{ $person['pregnancy'] }}</strong></td>
                            <td><span class="status-pill status-pill--{{ $person['status_tone'] }}">{{ $person['status'] }}</span></td>
                            <td><x-ui.weight-signal :label="$person['weight']" :tone="$person['weight_tone']" /></td>
                            <td>{{ $person['updated'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    @if(count($overview['actions']) > 5)
        <x-ui.modal id="dashboard-actions-modal" title="รายการที่ต้องจัดการวันนี้" size="wide" :filter="true" filter-placeholder="ค้นหาชื่อ HN มื้ออาหาร วันเวลา หรือประเภทงาน">
            <x-slot:subtitle>รายการอาหารแสดงแยก 1 แถวต่อ 1 มื้อ เลือกดูรายละเอียดหรือตรวจรายการได้โดยตรง</x-slot:subtitle>
            <div class="remi-modal__table-wrap"><table class="remi-responsive-table dashboard-actions-table"><thead><tr><th>ความเร่งด่วน</th><th>รายการ</th><th>ผู้รับบริการ</th><th>มื้อ / วัน / เวลา</th><th><span class="sr-only">ดำเนินการ</span></th></tr></thead><tbody>
                @foreach ($overview['actions'] as $action)
                    <tr>
                        <td data-label="ความเร่งด่วน"><span class="status-pill status-pill--{{ $action['tone'] }}">{{ $action['action'] }}</span></td>
                        <td data-label="รายการ"><strong>{{ $action['title'] }}</strong></td>
                        <td data-label="ผู้รับบริการ"><span class="modal-patient"><strong>{{ $action['patient_name'] ?? '-' }}</strong><small>HN {{ $action['hn'] ?? '-' }}</small></span></td>
                        <td data-label="มื้อ / วัน / เวลา"><span class="modal-meal-meta">{{ collect([$action['meal_label'] ?? null, $action['meal_date'] ?? null, $action['meal_time'] ?? null])->filter()->join(' • ') ?: '-' }}</span></td>
                        <td data-label="ดำเนินการ">
                            @if(($action['type'] ?? null) === 'food_review')
                                <button class="table-detail-button" type="button" data-modal-open="meal-detail-modal" data-detail-patient="{{ $action['patient_name'] ?? '-' }}" data-detail-hn="{{ $action['hn'] ?? '-' }}" data-detail-meal="{{ $action['meal_label'] ?? '-' }}" data-detail-date="{{ $action['meal_date'] ?? '-' }}" data-detail-time="{{ $action['meal_time'] ?? '-' }}" data-detail-image="{{ $action['image_url'] ?? '' }}" data-detail-foods="{{ $action['food_names'] ?? '-' }}" data-detail-energy="{{ $action['energy'] ?? '-' }}" data-detail-recommendation="{{ $action['recommendation'] ?? '-' }}" data-detail-reference="{{ $action['reference_id'] ?? '-' }}" data-detail-review-url="{{ $action['href'] ?? '' }}" data-detail-reviewable="true">ดูรายละเอียด →</button>
                            @elseif(!empty($action['href']))
                                <a class="table-detail-button" href="{{ $action['href'] }}">ดูผู้รับบริการ →</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody></table></div>
            <x-slot:footer>
                <div class="remi-modal__pagination" data-modal-pagination data-page-size="6">
                    <p data-modal-page-summary></p>
                    <nav aria-label="การแบ่งหน้ารายการที่ต้องจัดการ">
                        <button type="button" data-modal-page-prev>‹ ก่อนหน้า</button>
                        <span data-modal-page-numbers></span>
                        <button type="button" data-modal-page-next>ถัดไป ›</button>
                    </nav>
                </div>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    @if(count($overview['follow_up']) > 5)
        <x-ui.modal id="dashboard-follow-up-modal" title="ผู้รับบริการที่ควรติดตามทั้งหมด" size="wide" :filter="true" filter-placeholder="ค้นหาชื่อ HN หรือสถานะ">
            <div class="remi-modal__table-wrap"><table class="follow-up-table remi-responsive-table"><thead><tr><th>ผู้รับบริการ</th><th>อายุครรภ์</th><th>สถานะ</th><th>สัญญาณน้ำหนัก</th><th>อัปเดตล่าสุด</th></tr></thead><tbody>
                @foreach ($overview['follow_up'] as $person)
                    <tr><td data-label="ผู้รับบริการ"><a class="patient patient-context-link" href="{{ route('patients.show', $person['id']) }}"><span>{{ mb_substr($person['name'], 0, 1, 'UTF-8') }}</span><div><strong>{{ $person['name'] }}</strong><small>HN {{ $person['hn'] }}</small></div></a></td><td data-label="อายุครรภ์"><strong>{{ $person['pregnancy'] }}</strong></td><td data-label="สถานะ"><span class="status-pill status-pill--{{ $person['status_tone'] }}">{{ $person['status'] }}</span></td><td data-label="สัญญาณน้ำหนัก"><x-ui.weight-signal :label="$person['weight']" :tone="$person['weight_tone']" /></td><td data-label="อัปเดตล่าสุด">{{ $person['updated'] }}</td></tr>
                @endforeach
            </tbody></table></div>
            <x-slot:footer>
                <div class="remi-modal__pagination" data-modal-pagination data-page-size="7">
                    <p data-modal-page-summary></p>
                    <nav aria-label="การแบ่งหน้าผู้รับบริการที่ควรติดตาม">
                        <button type="button" data-modal-page-prev>‹ ก่อนหน้า</button>
                        <span data-modal-page-numbers></span>
                        <button type="button" data-modal-page-next>ถัดไป ›</button>
                    </nav>
                </div>
                <a class="ui-button ui-button--primary remi-modal__primary-link" href="{{ route('patients.index') }}">เปิดหน้ารายชื่อผู้รับบริการ</a>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    <x-ui.meal-detail-modal />
</div>
@endsection

@section('dashboard-floating-ui')
    <x-dashboard.chat-widget :chat="$overview['chat']" />
@endsection
