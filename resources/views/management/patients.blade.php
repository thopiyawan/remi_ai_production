@extends('layouts.dashboard')

@section('title', 'ผู้รับบริการ')

@section('content')
<div class="directory-content">
    <header class="directory-header">
        <div><h1>ผู้รับบริการ</h1><p>รายชื่อผู้รับบริการ ภายใต้การดูแลของแพทย์</p></div>
        <form class="dashboard-search" method="GET" action="{{ route('patients.index') }}">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <label class="sr-only" for="directory-patient-search">ค้นหาผู้รับบริการ</label>
            <input id="directory-patient-search" name="q" type="search" value="{{ $query }}" placeholder="ค้นหาชื่อหรือ HN">
            @if($activeFilter !== 'all')<input type="hidden" name="filter" value="{{ $activeFilter }}">@endif
            <input type="hidden" name="per_page" value="{{ $perPage }}">
        </form>
    </header>

    <nav class="filter-chips" aria-label="กรองผู้รับบริการ">
        @foreach ($filters as $filter)
            <a
                class="filter-chip {{ $activeFilter === $filter['key'] ? 'filter-chip--active' : '' }}"
                href="{{ route('patients.index', array_filter(['filter' => $filter['key'], 'q' => $query, 'per_page' => $perPage])) }}"
                @if($activeFilter === $filter['key']) aria-current="page" @endif
            >
                {{ $filter['label'] }} <span>{{ number_format($filter['count']) }}</span>
            </a>
        @endforeach
    </nav>

    @if ($patients->isNotEmpty())
        <div class="patient-directory-list">
            @foreach ($patients as $patient)
                <a class="patient-directory-link" href="{{ route('patients.show', $patient['id']) }}" aria-label="ดูรายละเอียด {{ $patient['name'] }}">
                    <x-ui.card class="patient-directory-row" as="article">
                        <div class="patient-directory-row__identity">
                            <span class="patient-directory-row__avatar" aria-hidden="true">{{ mb_substr($patient['name'], 0, 1, 'UTF-8') }}</span>
                            <div>
                                <h2>{{ $patient['name'] }}</h2>
                                <p>HN {{ $patient['hn'] ?? '-' }} • {{ $patient['age'] }} ปี • {{ $patient['pregnancy'] }} สัปดาห์</p>
                            </div>
                        </div>
                        <div class="patient-directory-row__signal"><x-ui.weight-signal :label="$patient['weight_label']" :tone="$patient['weight_tone']" /></div>
                        <span class="patient-directory-row__action">{{ $patient['action'] }} <span aria-hidden="true">→</span></span>
                    </x-ui.card>
                </a>
            @endforeach
        </div>

        <footer class="directory-pagination" aria-label="การแบ่งหน้า">
            <div class="directory-pagination__summary">
                <p>แสดง {{ $patients->firstItem() }}–{{ $patients->lastItem() }} จาก {{ number_format($patients->total()) }} รายการ</p>
                <form method="GET" action="{{ route('patients.index') }}">
                    @if($activeFilter !== 'all')<input type="hidden" name="filter" value="{{ $activeFilter }}">@endif
                    @if($query !== '')<input type="hidden" name="q" value="{{ $query }}">@endif
                    <x-ui.select class="ui-select-field--compact" id="patient-page-size" name="per_page" label="แสดงต่อหน้า" :options="$perPageOptions" :value="$perPage" :auto-submit="true" />
                    <noscript><button type="submit">ปรับจำนวน</button></noscript>
                </form>
            </div>
            <nav>
                @if ($patients->onFirstPage())
                    <span class="is-disabled">‹ ก่อนหน้า</span>
                @else
                    <a href="{{ $patients->previousPageUrl() }}">‹ ก่อนหน้า</a>
                @endif

                @foreach ($patients->getUrlRange(1, $patients->lastPage()) as $page => $url)
                    <a class="{{ $page === $patients->currentPage() ? 'is-current' : '' }}" href="{{ $url }}" @if($page === $patients->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                @endforeach

                @if ($patients->hasMorePages())
                    <a href="{{ $patients->nextPageUrl() }}">ถัดไป ›</a>
                @else
                    <span class="is-disabled">ถัดไป ›</span>
                @endif
            </nav>
        </footer>
    @else
        <x-ui.card class="directory-empty">
            <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
            <h2>ไม่พบผู้รับบริการในกลุ่มนี้</h2>
            <p>ลองเลือกตัวกรองอื่นเพื่อดูรายชื่อผู้รับบริการ</p>
        </x-ui.card>
    @endif
</div>
@endsection

@section('dashboard-floating-ui')
    <x-dashboard.chat-widget :chat="$chat" />
@endsection
