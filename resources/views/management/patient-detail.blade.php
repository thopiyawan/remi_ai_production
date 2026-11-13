@extends('layouts.dashboard')

@section('title', $patient['name'])

@section('content')
@php
    $weightGain = max(0, (float) ($patient['weight'] ?? 0) - (float) ($patient['pre_pregnancy_weight'] ?? 0));
    $dueDateParts = $patient['due_date_parts'] ?? ['date' => $patient['due_date'] ?? '-', 'year' => ''];
@endphp
<div class="patient-detail-content">
    <x-ui.card class="patient-hero">
        <div class="patient-hero__identity">
            <span class="patient-hero__avatar" aria-hidden="true">{{ mb_substr(preg_replace('/\s+/u', '', $patient['name']), 0, 2, 'UTF-8') }}</span>
            <div>
                <h1>{{ $patient['name'] }}</h1>
                <p>HN {{ $patient['hn'] ?? '-' }} • อายุ {{ $patient['age'] }} ปี</p>
                <div class="patient-hero__badges">
                    <span class="status-pill status-pill--info">{{ ($patient['is_first_pregnancy'] ?? null) === true ? 'ครรภ์แรก' : ((($patient['is_first_pregnancy'] ?? null) === false) ? 'เคยตั้งครรภ์' : 'ไม่มีข้อมูลลำดับครรภ์') }}</span>
                    <span class="status-pill status-pill--{{ $patient['risk_tone'] ?? 'muted' }}">{{ $patient['risk_label'] ?? 'ไม่มีข้อมูลความเสี่ยง' }}</span>
                </div>
            </div>
        </div>
        <dl class="patient-hero__facts">
            <div class="patient-hero__fact patient-hero__fact--pink"><dt>อายุครรภ์</dt><dd>{{ $patient['pregnancy'] }} <small>สัปดาห์</small></dd></div>
            <div class="patient-hero__fact patient-hero__fact--blue"><dt>น้ำหนัก</dt><dd>{{ number_format((float) ($patient['weight'] ?? 0), 1) }} <small>กก.</small></dd></div>
            <div class="patient-hero__fact patient-hero__fact--neutral">
                <dt>BMI</dt>
                <dd class="patient-hero__bmi-value">
                    <span>{{ $patient['bmi'] ?? '-' }}</span>
                    <x-ui.weight-signal class="patient-hero__weight-signal" label="" :tone="$patient['weight_status_tone'] ?? 'muted'" :aria-label="$patient['weight_status_label'] ?? 'ไม่มีข้อมูล'" />
                </dd>
            </div>
            <div class="patient-hero__fact patient-hero__fact--purple"><dt>กำหนดคลอด</dt><dd>{{ $dueDateParts['date'] }} <small>{{ $dueDateParts['year'] }}</small></dd></div>
        </dl>
    </x-ui.card>

    <x-patient.section-tabs :patient-id="$patient['id']" :active="$activeSection" />

    @if ($activeSection === 'overview')
        <h2 class="patient-overview-title">ภาพรวม</h2>

        <x-ui.card class="patient-weight-card">
            <header class="patient-card-heading"><div><h2>แนวโน้มน้ำหนัก</h2><p>เพิ่มขึ้น {{ number_format($weightGain, 1) }} กก. ตั้งแต่ก่อนตั้งครรภ์</p></div><x-ui.weight-signal :label="$patient['weight_status_label'] ?? 'ไม่มีข้อมูล'" :tone="$patient['weight_status_tone'] ?? 'muted'" /></header>
            <div class="patient-chart patient-chart--weight"><canvas data-chart="patient-weight" data-labels='@json($patient["weight_chart"]["labels"])' data-values='@json($patient["weight_chart"]["values"])'></canvas></div>
        </x-ui.card>

        <x-ui.card class="patient-health-summary">
            <h2>สรุปการบันทึกสุขภาพ</h2>
            <div class="patient-health-summary__grid">
                @foreach ($patient['health_summary'] as $item)
                    <div class="patient-health-tile patient-health-tile--{{ $item['tone'] }}">
                        <p><span aria-hidden="true"></span>{{ $item['label'] }}</p>
                        <strong>{{ $item['value'] }}</strong>
                        <small>{{ $item['detail'] }}</small>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <div class="patient-bottom-grid">
            <x-ui.card class="patient-compact-card">
                <header><h2>ประเด็นที่ต้องเฝ้าระวัง</h2><span class="status-pill status-pill--warning">ติดตาม {{ count($patient['follow_ups'] ?? []) }} เรื่อง</span></header>
                <ul>
                    @forelse ($patient['follow_ups'] ?? [] as $item)
                        <li><span class="is-{{ $item['tone'] }}"></span><div>{{ $item['title'] }}<small>{{ $item['detail'] }}</small></div><a href="{{ route($item['route'] ?? 'patients.health', $patient['id']) }}">เปิดดู →</a></li>
                    @empty
                        <li class="patient-empty-state"><span class="patient-empty-state__icon"><i class="fa-solid fa-check" aria-hidden="true"></i></span><div><strong>ยังไม่มีประเด็นที่ต้องเฝ้าระวัง</strong><small>ข้อมูลล่าสุดอยู่ในเกณฑ์ที่ไม่ต้องติดตามเป็นพิเศษ</small></div></li>
                    @endforelse
                </ul>
            </x-ui.card>
            <x-ui.card class="patient-compact-card">
                <header><h2>แผนดูแลและนัดหมาย</h2>@if(count($patient['care_plans'] ?? []) > 3)<button class="ui-text-button" type="button" data-modal-open="care-plans-modal">ดูทั้งหมด ({{ count($patient['care_plans']) }}) →</button>@endif</header>
                <ul class="patient-appointments">
                    @forelse (array_slice($patient['care_plans'] ?? [], 0, 3) as $plan)
                        <li><time>{{ $plan['date'] }}</time><div>{{ $plan['title'] }}<small>{{ $plan['detail'] }}</small></div><span class="status-pill status-pill--{{ $plan['tone'] }}">{{ $plan['status'] }}</span></li>
                    @empty
                        <li class="patient-empty-state patient-empty-state--neutral"><span class="patient-empty-state__icon"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span><div><strong>ยังไม่มีแผนดูแลหรือนัดหมาย</strong><small>เมื่อมีการบันทึก แผนและวันนัดหมายจะแสดงที่นี่</small></div></li>
                    @endforelse
                </ul>
            </x-ui.card>
        </div>
        @if(count($patient['care_plans'] ?? []) > 3)
            <x-ui.modal id="care-plans-modal" title="แผนดูแลและนัดหมายทั้งหมด" size="wide" :filter="true">
                <div class="remi-modal__table-wrap"><table class="remi-responsive-table"><thead><tr><th>วันที่</th><th>แผนการดูแล</th><th>รายละเอียด</th><th>สถานะ</th></tr></thead><tbody>
                    @foreach ($patient['care_plans'] as $plan)<tr><td data-label="วันที่"><time>{{ $plan['date'] }}</time></td><td data-label="แผนการดูแล"><strong>{{ $plan['title'] }}</strong></td><td data-label="รายละเอียด">{{ $plan['detail'] ?: '-' }}</td><td data-label="สถานะ"><span class="status-pill status-pill--{{ $plan['tone'] }}">{{ $plan['status'] }}</span></td></tr>@endforeach
                </tbody></table></div>
                <x-slot:footer><div class="remi-modal__pagination" data-modal-pagination data-page-size="8"><p data-modal-page-summary></p><nav aria-label="การแบ่งหน้าแผนดูแล"><button type="button" data-modal-page-prev>‹ ก่อนหน้า</button><span data-modal-page-numbers></span><button type="button" data-modal-page-next>ถัดไป ›</button></nav></div></x-slot:footer>
            </x-ui.modal>
        @endif
    @elseif ($activeSection === 'health')
        <h2 class="patient-overview-title">น้ำหนัก ออกกำลังกาย ลูกดิ้น</h2>

        <div class="patient-health-top-grid">
            <x-ui.card class="patient-health-chart-card">
                <header class="patient-card-heading">
                    <div><h2>แนวโน้มน้ำหนักตามอายุครรภ์</h2><p>เปรียบเทียบกับช่วงน้ำหนักที่แนะนำ</p></div>
                    <div class="patient-current-weight"><strong>{{ number_format((float) ($patient['weight'] ?? 0), 1) }} กก.</strong><x-ui.weight-signal :label="$patient['weight_status_label'] ?? 'ไม่มีข้อมูล'" :tone="$patient['weight_status_tone'] ?? 'muted'" /></div>
                </header>
                <div class="patient-chart patient-chart--health-weight">
                    <canvas data-chart="health-weight" data-labels='@json($patient["health"]["weight"]["labels"])' data-values='@json($patient["health"]["weight"]["values"])' data-min='@json($patient["health"]["weight"]["recommended_min"])' data-max='@json($patient["health"]["weight"]["recommended_max"])'></canvas>
                </div>
            </x-ui.card>

            <x-ui.card class="patient-health-history-card">
                <div class="card-heading card-heading--action"><h2>ประวัติล่าสุด</h2>@if(count($patient['health']['latest']) > 5)<button class="ui-text-button" type="button" data-modal-open="health-latest-modal">ดูทั้งหมด ({{ count($patient['health']['latest']) }}) →</button>@endif</div>
                <p>ข้อมูลเรียงตามวันและเวลาที่บันทึก</p>
                <ul class="health-history-list">
                    @foreach (array_slice($patient['health']['latest'], 0, 5) as $item)
                        <li class="health-history-item health-history-item--{{ $item['tone'] }}">
                            <span class="health-history-icon" aria-hidden="true"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                            <div class="health-history-copy"><strong>{{ $item['label'] }}</strong><time>{{ $item['time'] }}</time></div>
                            <span class="health-history-value">{{ $item['value'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        </div>

        <div class="patient-health-bottom-grid">
            <x-ui.card class="patient-health-chart-card">
                <header class="patient-card-heading"><h2>ประวัติการออกกำลังกาย</h2><div class="patient-card-actions"><span class="patient-card-stat">{{ number_format(array_sum($patient['health']['exercise']['values'] ?? [])) }} นาที</span>@if(count($patient['health']['exercise']['history']) > 5)<button class="ui-text-button" type="button" data-modal-open="exercise-history-modal">ดูทั้งหมด</button>@endif</div></header>
                <div class="exercise-intensity-legend" aria-label="ระดับความหนักของการออกกำลังกาย">
                    <span class="exercise-intensity exercise-intensity--light"><i aria-hidden="true"></i>เบา</span>
                    <span class="exercise-intensity exercise-intensity--medium"><i aria-hidden="true"></i>ปานกลาง</span>
                    <span class="exercise-intensity exercise-intensity--high"><i aria-hidden="true"></i>ค่อนข้างหนัก</span>
                </div>
                <div class="patient-chart patient-chart--exercise"><canvas data-chart="health-exercise" data-labels='@json($patient["health"]["exercise"]["labels"])' data-values='@json($patient["health"]["exercise"]["values"])' data-light='@json($patient["health"]["exercise"]["light"])' data-medium='@json($patient["health"]["exercise"]["medium"])' data-high='@json($patient["health"]["exercise"]["high"])' data-unknown='@json($patient["health"]["exercise"]["unknown"])'></canvas></div>
                <ul class="health-record-list">
                    @foreach (array_slice($patient['health']['exercise']['history'], 0, 5) as $item)
                        <li>
                            <time class="exercise-record-time"><span>{{ $item['date_label'] ?? $item['time'] }}</span><small>{{ $item['time_label'] ?? '' }}</small></time>
                            <strong class="exercise-record-name">{{ $item['type'] }}</strong>
                            <span class="exercise-record-duration">{{ $item['duration'] }}</span>
                            <em class="exercise-intensity-label exercise-intensity-label--{{ $item['intensity_tone'] ?? 'unknown' }}">{{ $item['intensity'] }}</em>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>

            <x-ui.card class="patient-health-chart-card">
                <header class="patient-card-heading"><h2>การนับลูกดิ้น</h2>@if(count($patient['health']['fetal']['history']) > 5)<button class="ui-text-button" type="button" data-modal-open="fetal-history-modal">ดูทั้งหมด</button>@endif</header>
                <div class="exercise-intensity-legend exercise-intensity-legend--placeholder" aria-hidden="true"></div>
                <div class="patient-chart patient-chart--fetal"><canvas data-chart="health-fetal" data-labels='@json($patient["health"]["fetal"]["labels"])' data-values='@json($patient["health"]["fetal"]["values"])'></canvas></div>
                <ul class="health-record-list health-record-list--fetal">
                    @foreach (array_slice($patient['health']['fetal']['history'], 0, 5) as $item)
                        <li><time>{{ $item['date'] }}</time><strong>{{ $item['value'] }}</strong></li>
                    @endforeach
                </ul>
            </x-ui.card>
        </div>
        @if(count($patient['health']['latest']) > 5)
            <x-ui.modal id="health-latest-modal" title="ประวัติสุขภาพทั้งหมด" size="wide" :filter="true">
                <div class="remi-modal__table-wrap"><table class="remi-responsive-table"><thead><tr><th>หมวด</th><th>วันที่และเวลา</th><th>ค่าที่บันทึก</th></tr></thead><tbody>
                    @foreach ($patient['health']['latest'] as $item)<tr class="health-history-item--{{ $item['tone'] }}"><td data-label="หมวด"><span class="modal-category"><span class="health-history-icon" aria-hidden="true"><i class="fa-solid {{ $item['icon'] }}"></i></span><strong>{{ $item['label'] }}</strong></span></td><td data-label="วันที่และเวลา"><time>{{ $item['time'] }}</time></td><td data-label="ค่าที่บันทึก"><strong>{{ $item['value'] }}</strong></td></tr>@endforeach
                </tbody></table></div>
                <x-slot:footer><div class="remi-modal__pagination" data-modal-pagination data-page-size="8"><p data-modal-page-summary></p><nav aria-label="การแบ่งหน้าประวัติสุขภาพ"><button type="button" data-modal-page-prev>‹ ก่อนหน้า</button><span data-modal-page-numbers></span><button type="button" data-modal-page-next>ถัดไป ›</button></nav></div></x-slot:footer>
            </x-ui.modal>
        @endif
        @if(count($patient['health']['exercise']['history']) > 5)
            <x-ui.modal id="exercise-history-modal" title="ประวัติการออกกำลังกายทั้งหมด" size="wide" :filter="true">
                <div class="remi-modal__table-wrap"><table class="remi-responsive-table"><thead><tr><th>วันที่</th><th>กิจกรรม</th><th>ระยะเวลา</th><th>ความหนัก</th></tr></thead><tbody>@foreach ($patient['health']['exercise']['history'] as $item)<tr><td data-label="วันที่"><time>{{ $item['time'] }}</time></td><td data-label="กิจกรรม"><strong>{{ $item['type'] }}</strong></td><td data-label="ระยะเวลา">{{ $item['duration'] }}</td><td data-label="ความหนัก"><span class="exercise-intensity-label exercise-intensity-label--{{ $item['intensity_tone'] ?? 'unknown' }}">{{ $item['intensity'] }}</span></td></tr>@endforeach</tbody></table></div>
                <x-slot:footer><div class="remi-modal__pagination" data-modal-pagination data-page-size="8"><p data-modal-page-summary></p><nav aria-label="การแบ่งหน้าประวัติการออกกำลังกาย"><button type="button" data-modal-page-prev>‹ ก่อนหน้า</button><span data-modal-page-numbers></span><button type="button" data-modal-page-next>ถัดไป ›</button></nav></div></x-slot:footer>
            </x-ui.modal>
        @endif
        @if(count($patient['health']['fetal']['history']) > 5)
            <x-ui.modal id="fetal-history-modal" title="ประวัติการนับลูกดิ้นทั้งหมด" size="wide" :filter="true">
                <div class="remi-modal__table-wrap"><table class="remi-responsive-table"><thead><tr><th>วันที่</th><th>จำนวนลูกดิ้น</th></tr></thead><tbody>@foreach ($patient['health']['fetal']['history'] as $item)<tr><td data-label="วันที่"><time>{{ $item['date'] }}</time></td><td data-label="จำนวนลูกดิ้น"><strong>{{ $item['value'] }}</strong></td></tr>@endforeach</tbody></table></div>
                <x-slot:footer><div class="remi-modal__pagination" data-modal-pagination data-page-size="8"><p data-modal-page-summary></p><nav aria-label="การแบ่งหน้าประวัติการนับลูกดิ้น"><button type="button" data-modal-page-prev>‹ ก่อนหน้า</button><span data-modal-page-numbers></span><button type="button" data-modal-page-next>ถัดไป ›</button></nav></div></x-slot:footer>
            </x-ui.modal>
        @endif
    @elseif ($activeSection === 'nutrition')
        <div class="patient-section-heading">
            <h2 class="patient-overview-title">อาหาร</h2>
            <form class="nutrition-period-filter" method="GET" action="{{ route('patients.nutrition', $patient['id']) }}">
                <label for="nutrition-date-range">ช่วงวันที่</label>
                <input id="nutrition-date-range" type="text" value="{{ $dateFrom }} ถึง {{ $dateTo }}" readonly data-date-range data-auto-submit data-date-from="{{ $dateFrom }}" data-date-to="{{ $dateTo }}">
                <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" value="{{ $dateTo }}">
            </form>
        </div>

        <section class="nutrition-summary-grid" aria-label="สรุปอาหาร">
            @foreach ($patient['nutrition']['summary'] as $item)
                <div class="nutrition-summary nutrition-summary--{{ $item['tone'] }}"><p>{{ $item['label'] }}</p><strong>{{ $item['value'] }}</strong><small>{{ $item['detail'] }}</small></div>
            @endforeach
        </section>

        <x-ui.card class="nutrition-insights">
            <h2>สรุปจากข้อมูลย้อนหลัง</h2>
            <div>
                @foreach ($patient['nutrition']['insights'] as $item)
                    <article class="nutrition-insight nutrition-insight--{{ $item['tone'] }}"><strong>{{ $item['title'] }}</strong><p>{{ $item['detail'] }}</p></article>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card class="nutrition-energy-card">
            <header class="patient-card-heading"><h2>พลังงานที่เหมาะสมเทียบกับที่บริโภค</h2>@if (($patient['nutrition']['energy']['deficit'] ?? null) !== null)<span class="status-pill status-pill--warning">ต่ำกว่าเป้าหมายสะสม {{ number_format((float) $patient['nutrition']['energy']['deficit']) }} kcal</span>@endif</header>
            <div class="patient-chart patient-chart--nutrition"><canvas data-chart="nutrition-energy" data-target="{{ $patient['nutrition']['energy']['target'] ?? 0 }}" data-labels='@json($patient["nutrition"]["energy"]["labels"])' data-carbs='@json($patient["nutrition"]["energy"]["carbs"])' data-protein='@json($patient["nutrition"]["energy"]["protein"])' data-fat='@json($patient["nutrition"]["energy"]["fat"])'></canvas></div>
        </x-ui.card>

        <div class="nutrition-detail-grid">
            <x-ui.card class="nutrition-nutrients-card">
                <header><h2>สารอาหารที่บริโภคย้อนหลัง</h2><span>ค่าเฉลี่ยจากวันที่มีข้อมูล</span></header>
                <div class="nutrition-nutrient-list">
                    @foreach ($patient['nutrition']['nutrients'] as $item)
                        <div><span>{{ $item['label'] }}</span><div class="nutrition-progress"><i class="nutrition-progress--{{ $item['tone'] }}" style="width: {{ $item['progress'] }}%"></i></div><strong>{{ $item['value'] }} {{ $item['unit'] }}</strong><small>{{ $item['target'] === null ? 'ไม่มีค่าเป้าหมาย' : 'เป้า ' . $item['target'] . ' ' . $item['unit'] }}</small></div>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card class="nutrition-vitamins-card">
                <h2>ประวัติการรับวิตามิน 7 วันย้อนหลัง</h2>
                <div class="vitamin-calendar">
                    <div class="vitamin-calendar__days"><span></span>@foreach ($patient['nutrition']['vitamin_days'] ?? [] as $day)<span><strong>{{ $day['day'] }}</strong><small>{{ $day['month'] }}</small></span>@endforeach</div>
                    @foreach ($patient['nutrition']['vitamins'] as $vitamin)
                        <div class="vitamin-calendar__row"><strong>{{ $vitamin['label'] }}</strong>@foreach ($vitamin['days'] as $taken)<i class="{{ $taken ? 'is-taken' : '' }}" aria-label="{{ $taken ? 'รับประทานแล้ว' : 'ไม่ได้บันทึก' }}"></i>@endforeach</div>
                    @endforeach
                </div>
                <p class="vitamin-legend"><i class="is-taken"></i> รับประทานแล้ว <i></i> ไม่ได้บันทึก</p>
                <div class="vitamin-warning"><strong>{{ $patient['nutrition']['vitamin_warning'] ?? 'ยังไม่มีข้อมูลวิตามินในช่วงที่เลือก' }}</strong></div>
            </x-ui.card>
        </div>

        <x-ui.card class="nutrition-meal-history">
            <header><h2>ประวัติการรับประทานอาหารย้อนหลัง</h2>@if(count($patient['nutrition']['meals']) > 10)<button class="ui-text-button" type="button" data-modal-open="meal-history-modal">ดูทั้งหมด ({{ number_format((int) ($patient['nutrition']['meal_count'] ?? 0)) }}) →</button>@else<span>{{ number_format((int) ($patient['nutrition']['meal_count'] ?? 0)) }} มื้อในช่วงที่เลือก</span>@endif</header>
            <div class="nutrition-table-wrap">
                <table>
                    <thead><tr><th>วันที่</th><th>มื้อ/เวลา</th><th>รายการอาหาร</th><th>พลังงาน</th><th>สถานะ</th><th><span class="sr-only">ดำเนินการ</span></th></tr></thead>
                    <tbody>
                        @foreach (array_slice($patient['nutrition']['meals'], 0, 10) as $meal)
                            <tr><td>{{ $meal['date'] }}</td><td><span class="meal-dot meal-dot--{{ $meal['meal_tone'] }}"></span>{{ $meal['meal'] }} {{ $meal['time'] }}</td><td><strong>{{ $meal['food'] }}</strong></td><td>{{ $meal['energy'] }}</td><td><span class="status-pill status-pill--{{ $meal['tone'] }}">{{ $meal['status'] }}</span></td><td><x-dashboard.meal-actions :meal="$meal" :patient-name="$patient['name']" :hn="$patient['hn']" /></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
        @if(count($patient['nutrition']['meals']) > 10)
            <x-ui.modal id="meal-history-modal" title="ประวัติการรับประทานอาหารทั้งหมด" size="wide" :filter="true" filter-placeholder="ค้นหาวันที่ มื้ออาหาร หรือรายการอาหาร">
                <div class="remi-modal__table-wrap"><table class="nutrition-modal-table remi-responsive-table"><thead><tr><th>วันที่</th><th>มื้อ/เวลา</th><th>รายการอาหาร</th><th>พลังงาน</th><th>สถานะ</th><th><span class="sr-only">ดำเนินการ</span></th></tr></thead><tbody>@foreach ($patient['nutrition']['meals'] as $meal)<tr><td data-label="วันที่">{{ $meal['date'] }}</td><td data-label="มื้อ/เวลา"><span class="meal-dot meal-dot--{{ $meal['meal_tone'] }}"></span>{{ $meal['meal'] }} {{ $meal['time'] }}</td><td data-label="รายการอาหาร"><strong>{{ $meal['food'] }}</strong></td><td data-label="พลังงาน">{{ $meal['energy'] }}</td><td data-label="สถานะ"><span class="status-pill status-pill--{{ $meal['tone'] }}">{{ $meal['status'] }}</span></td><td data-label="ดำเนินการ"><x-dashboard.meal-actions :meal="$meal" :patient-name="$patient['name']" :hn="$patient['hn']" /></td></tr>@endforeach</tbody></table></div>
                <x-slot:footer><div class="remi-modal__pagination" data-modal-pagination data-page-size="8"><p data-modal-page-summary></p><nav aria-label="การแบ่งหน้าประวัติอาหาร"><button type="button" data-modal-page-prev>‹ ก่อนหน้า</button><span data-modal-page-numbers></span><button type="button" data-modal-page-next>ถัดไป ›</button></nav></div></x-slot:footer>
            </x-ui.modal>
        @endif
        <x-ui.meal-detail-modal />
    @else
        <h2 class="patient-overview-title">น้ำตาลในเลือด</h2>

        <section class="glucose-summary-grid" aria-label="สรุปน้ำตาลในเลือด">
            @foreach ($patient['glucose']['summary'] as $item)
                <div class="glucose-summary glucose-summary--{{ $item['tone'] }}"><p>{{ $item['label'] }}</p><strong>{{ $item['value'] }}</strong><small>{{ $item['detail'] }}</small></div>
            @endforeach
        </section>

        <x-ui.card class="glucose-issues-card">
            <h2>ประเด็นที่ต้องติดตาม</h2>
            <div>@foreach ($patient['glucose']['issues'] as $item)<article class="glucose-issue glucose-issue--{{ $item['tone'] }}"><strong>{{ $item['title'] }}</strong><p>{{ $item['detail'] }}</p></article>@endforeach</div>
        </x-ui.card>

        <x-ui.card class="glucose-chart-card">
            <header class="patient-card-heading"><div><h2>แนวโน้มน้ำตาลในเลือด</h2><p>ก่อนอาหารและหลังอาหาร 1 ชั่วโมง • ช่วงวันที่เลือก</p></div><div class="glucose-chart-badges">@if($patient['glucose']['target_range'] ?? null)<span class="status-pill status-pill--success">ช่วงเป้าหมาย {{ $patient['glucose']['target_range'] }}</span>@endif<span class="status-pill status-pill--danger">มีค่าเกินเป้าหมาย {{ $patient['glucose']['high_count'] ?? 0 }} ครั้ง</span></div></header>
            <div class="patient-chart patient-chart--clinical"><span class="patient-chart-unit">{{ $patient['glucose']['unit'] ?? 'mg/dL' }}</span><canvas data-chart="patient-glucose" data-labels='@json($patient["glucose_chart"]["labels"])' data-readings='@json($patient["glucose_chart"]["readings"])'></canvas></div>
            <div class="clinical-chart-legend" aria-label="คำอธิบายกราฟ">
                <span><i class="clinical-marker clinical-marker--triangle"></i>ก่อนอาหาร</span>
                <span><i class="clinical-marker clinical-marker--circle"></i>หลังอาหาร 1 ชม.</span>
                <span><i class="clinical-marker clinical-marker--square"></i>ก่อนนอน</span>
                <span><i class="clinical-marker clinical-marker--high"></i>เกินเกณฑ์</span>
                <span><i class="clinical-marker clinical-marker--line"></i>เกณฑ์วินิจฉัย</span>
            </div>
        </x-ui.card>

        <x-ui.card class="glucose-chart-card">
            <header class="patient-card-heading"><h2>สรุปค่าน้ำตาลในเลือด</h2></header>
            <div class="patient-chart patient-chart--glucose-summary"><canvas data-chart="glucose-summary" data-labels='@json($patient["glucose"]["chart_summary"]["labels"])' data-low='@json($patient["glucose"]["chart_summary"]["low"])' data-normal='@json($patient["glucose"]["chart_summary"]["normal"])' data-high='@json($patient["glucose"]["chart_summary"]["high"])' data-no-data='@json($patient["glucose"]["chart_summary"]["no_data"])'></canvas></div>
        </x-ui.card>

        <x-ui.card class="glucose-history-card">
            <header><h2>ประวัติการบันทึกน้ำตาล</h2>@if(count($patient['glucose']['records']) > 10)<button class="ui-text-button" type="button" data-modal-open="glucose-history-modal">ดูทั้งหมด ({{ count($patient['glucose']['records']) }}) →</button>@endif</header>
            <div class="nutrition-table-wrap"><table><thead><tr><th>วันที่/เวลา</th><th>ช่วงตรวจ</th><th>ค่า</th><th>เป้าหมาย</th><th>สถานะ</th></tr></thead><tbody>@foreach (array_slice($patient['glucose']['records'], 0, 10) as $record)<tr><td>{{ $record['date'] }}</td><td>{{ $record['period'] }}</td><td><strong class="glucose-value glucose-value--{{ $record['tone'] }}">{{ $record['value'] }}</strong></td><td>{{ $record['target'] }}</td><td><span class="status-pill status-pill--{{ $record['tone'] }}">{{ $record['status'] }}</span></td></tr>@endforeach</tbody></table></div>
        </x-ui.card>

        @if(count($patient['glucose']['records']) > 10)
            <x-ui.modal id="glucose-history-modal" title="ประวัติการบันทึกน้ำตาลทั้งหมด" size="wide" :filter="true" filter-placeholder="ค้นหาวันที่ ช่วงตรวจ หรือสถานะ">
                <div class="remi-modal__table-wrap"><table class="nutrition-modal-table remi-responsive-table"><thead><tr><th>วันที่/เวลา</th><th>ช่วงตรวจ</th><th>ค่า</th><th>เป้าหมาย</th><th>สถานะ</th></tr></thead><tbody>@foreach ($patient['glucose']['records'] as $record)<tr><td data-label="วันที่/เวลา">{{ $record['date'] }}</td><td data-label="ช่วงตรวจ">{{ $record['period'] }}</td><td data-label="ค่า"><strong class="glucose-value glucose-value--{{ $record['tone'] }}">{{ $record['value'] }}</strong></td><td data-label="เป้าหมาย">{{ $record['target'] }}</td><td data-label="สถานะ"><span class="status-pill status-pill--{{ $record['tone'] }}">{{ $record['status'] }}</span></td></tr>@endforeach</tbody></table></div>
                <x-slot:footer><div class="remi-modal__pagination" data-modal-pagination data-page-size="8"><p data-modal-page-summary></p><nav aria-label="การแบ่งหน้าประวัติน้ำตาล"><button type="button" data-modal-page-prev>‹ ก่อนหน้า</button><span data-modal-page-numbers></span><button type="button" data-modal-page-next>ถัดไป ›</button></nav></div></x-slot:footer>
            </x-ui.modal>
        @endif

        <x-ui.card class="insulin-card" data-insulin-card>
            <header class="insulin-card__header"><div><h2>ประวัติการปรับยา Insulin</h2><p>รายการล่าสุดโดย <span data-insulin-updated-by>{{ $patient['glucose']['insulin']['updated_by'] }}</span> • <span data-insulin-updated-at>{{ $patient['glucose']['insulin']['updated_at'] }}</span></p></div><div><button class="ui-button insulin-button insulin-button--secondary" type="button" data-modal-open="insulin-history-modal">ดูประวัติย้อนหลัง</button><button class="ui-button insulin-button" type="button" data-modal-open="insulin-adjustment-modal">+ เตรียมคำสั่งปรับยา</button></div></header>
            <div class="insulin-latest">
                <div><span>ชนิด</span><strong data-insulin-type>{{ $patient['glucose']['insulin']['type'] }}</strong></div>
                <div><span>เวลา</span><strong data-insulin-time>{{ $patient['glucose']['insulin']['time'] }}</strong></div>
                <div><span>ขนาดเดิม</span><strong><span data-insulin-previous>{{ $patient['glucose']['insulin']['previous_dose'] }}</span> units</strong></div>
                <div><span>ขนาดปัจจุบัน</span><strong><span data-insulin-current>{{ $patient['glucose']['insulin']['current_dose'] }}</span> units</strong></div>
                <div><span>วิธีฉีด</span><strong data-insulin-method>{{ $patient['glucose']['insulin']['method'] }}</strong></div>
            </div>
            <div class="insulin-notes"><article><strong>เหตุผลการปรับ</strong><p data-insulin-reason>{{ $patient['glucose']['insulin']['reason'] }}</p></article><article><strong>คำแนะนำผู้ป่วย</strong><p data-insulin-instruction>{{ $patient['glucose']['insulin']['instruction'] }}</p></article></div>

        </x-ui.card>

        <x-ui.modal id="insulin-history-modal" title="ประวัติการปรับยา Insulin" size="wide" :filter="true" filter-placeholder="ค้นหาชนิดยา เหตุผล หรือผู้ปรับ">
            @if(count($patient['glucose']['insulin_history'] ?? []))<div class="remi-modal__table-wrap"><table class="nutrition-modal-table remi-responsive-table"><thead><tr><th>วันที่</th><th>ชนิด/เวลา</th><th>ขนาดเดิม</th><th>ขนาดใหม่</th><th>เหตุผล</th><th>ผู้ปรับ</th></tr></thead><tbody>@foreach($patient['glucose']['insulin_history'] as $item)<tr><td data-label="วันที่">{{ $item['date'] }}</td><td data-label="ชนิด/เวลา">{{ $item['type'] }} • {{ $item['time'] }}</td><td data-label="ขนาดเดิม">{{ $item['previous_dose'] }} units</td><td data-label="ขนาดใหม่"><strong>{{ $item['current_dose'] }} units</strong></td><td data-label="เหตุผล">{{ $item['reason'] }}</td><td data-label="ผู้ปรับ">{{ $item['updated_by'] }}</td></tr>@endforeach</tbody></table></div>@else<p class="remi-empty-state">ยังไม่มีประวัติการปรับยาใน API</p>@endif
        </x-ui.modal>

        <x-ui.modal id="insulin-adjustment-modal" title="เตรียมคำสั่งปรับยา Insulin" size="wide">
            <x-slot:subtitle>กรอกข้อมูลและตรวจทานคำสั่งปรับยาก่อนบันทึก</x-slot:subtitle>
            <div class="api-ready" data-insulin-form-status aria-live="polite"><strong>ตรวจสอบข้อมูลก่อนบันทึก</strong><p>กรุณาตรวจสอบรายละเอียดคำสั่งปรับยาให้ครบถ้วน</p></div>
            <form id="insulin-adjustment-form" class="insulin-form insulin-form--modal" data-insulin-adjustment-form data-patient-id="{{ $patient['id'] }}">
                <div class="insulin-form__fields">
                    <x-ui.select class="insulin-form__span-3" name="type" label="ชนิดอินซูลิน" :required="true" :options="['rapid' => 'Rapid-acting', 'short' => 'Short-acting', 'intermediate' => 'Intermediate-acting', 'long' => 'Long-acting', 'premixed' => 'Premixed']" value="rapid" />
                    <x-ui.select class="insulin-form__span-3" name="period" label="ช่วงเวลาฉีด" :required="true" :options="['before_breakfast' => 'ก่อนอาหารเช้า', 'before_lunch' => 'ก่อนอาหารกลางวัน', 'before_dinner' => 'ก่อนอาหารเย็น', 'bedtime' => 'ก่อนนอน']" value="before_breakfast" />
                    <label class="insulin-form__span-2">เวลา (ถ้ามี)<input name="time" type="time"></label>
                    <label class="insulin-form__span-2"><span>ขนาดปัจจุบัน<span class="ui-required-mark" aria-hidden="true">*</span><span class="sr-only"> จำเป็น</span></span><input name="previous_dose" type="number" value="{{ $patient['glucose']['insulin']['current_dose'] }}" min="0" step="0.5" required></label>
                    <label class="insulin-form__span-2"><span>ขนาดใหม่<span class="ui-required-mark" aria-hidden="true">*</span><span class="sr-only"> จำเป็น</span></span><input name="current_dose" type="number" placeholder="units" min="0" step="0.5" required></label>
                    <x-ui.select class="insulin-form__span-3" name="method" label="วิธีฉีด" :required="true" :options="['subcutaneous' => 'ฉีดใต้ผิวหนัง', 'pen' => 'ปากกาอินซูลิน', 'syringe' => 'ไซริงจ์']" value="subcutaneous" />
                    <x-ui.select class="insulin-form__span-5" name="reason" label="เหตุผลหลัก" :required="true" :options="['high_before' => 'ค่าน้ำตาลก่อนอาหารสูง', 'high_after' => 'ค่าน้ำตาลหลังอาหารสูง', 'hypoglycemia' => 'มีภาวะน้ำตาลต่ำ', 'care_plan' => 'ปรับตามแผนการรักษา', 'other' => 'อื่น ๆ']" value="high_before" />
                    <label class="insulin-form__span-4 insulin-form__compact-field">รายละเอียดเพิ่มเติม<textarea name="reason_detail" rows="1" placeholder="ระบุข้อมูลทางคลินิกที่จำเป็น"></textarea></label>
                    <label class="insulin-form__full">คำแนะนำผู้รับบริการ<textarea name="instruction" rows="3" placeholder="วิธีใช้ การติดตาม และอาการที่ควรพบแพทย์"></textarea></label>
                </div>
            </form>
            <x-slot:footer><button type="button" class="ui-button insulin-button insulin-button--secondary" data-modal-close>ยกเลิก</button><button type="submit" form="insulin-adjustment-form" class="ui-button insulin-button">บันทึกคำสั่งยา</button></x-slot:footer>
        </x-ui.modal>
    @endif
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.11/flatpickr.min.css">
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.11/flatpickr.min.js" defer></script>
@endpush

@section('dashboard-floating-ui')
    <x-dashboard.chat-widget :chat="$chat" />
@endsection
