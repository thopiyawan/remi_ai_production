@extends('layouts.dashboard')

@section('title', 'ข้อความที่สนทนาทั้งหมด')

@section('content')
<div class="dashboard-content conversations-page" data-conversations-page data-send-url="{{ route('conversations.messages.store') }}">
    <header class="dashboard-header conversations-page__header">
        <div><h1>ข้อความที่สนทนาทั้งหมด</h1><p>ประวัติกลุ่มและข้อความ 1:1 • {{ number_format($conversationPagination['total'] ?? 0) }} ข้อความจาก API</p></div>
        <form class="conversations-api-search" method="GET" action="{{ route('conversations.index') }}">
            @if (($selected['type'] ?? null) === 'direct')<input type="hidden" name="conversation" value="{{ $selected['id'] }}">@endif
            <label class="conversations-global-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span class="sr-only">ค้นหาข้อความ</span><input type="search" name="keyword" value="{{ $conversationSearch['keyword'] ?? '' }}" placeholder="ค้นหาข้อความ"></label>
            <label class="conversations-date-range"><i class="fa-regular fa-calendar" aria-hidden="true"></i><span class="sr-only">ช่วงวันที่</span><input id="conversation-date-range" type="text" value="{{ ($conversationSearch['start_date'] ?? '') && ($conversationSearch['end_date'] ?? '') ? $conversationSearch['start_date'].' ถึง '.$conversationSearch['end_date'] : '' }}" placeholder="เลือกช่วงวันที่" readonly data-date-range data-date-from="{{ $conversationSearch['start_date'] ?? '' }}" data-date-to="{{ $conversationSearch['end_date'] ?? '' }}"></label>
            <input type="hidden" name="start_date" value="{{ $conversationSearch['start_date'] ?? '' }}" data-date-range-from>
            <input type="hidden" name="end_date" value="{{ $conversationSearch['end_date'] ?? '' }}" data-date-range-to>
            <button type="submit">ค้นหา</button>
            @if (!empty($conversationSearch['keyword']) || !empty($conversationSearch['start_date']) || !empty($conversationSearch['end_date']))
                <a href="{{ route('conversations.index') }}">ล้าง</a>
            @endif
        </form>
    </header>

    <div class="conversations-workspace">
        <aside class="conversation-list" aria-label="รายการสนทนา">
            <nav class="conversation-filters" aria-label="ตัวกรองข้อความ">
                <button class="is-active" type="button" data-conversation-filter="all">ทั้งหมด <strong>{{ $conversationStats['all'] }}</strong></button>
                <button type="button" data-conversation-filter="unread">ยังไม่อ่าน <strong>{{ $conversationStats['unread'] }}</strong></button>
                <button type="button" data-conversation-filter="group">กลุ่ม <strong>{{ $conversationStats['group'] }}</strong></button>
            </nav>
            <label class="conversation-list-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span class="sr-only">ค้นหาชื่อหรือข้อความ</span><input type="search" placeholder="ค้นหาชื่อหรือข้อความ" data-conversation-search></label>
            <div class="conversation-list__items" data-conversation-list>
                @foreach ($conversations as $conversation)
                    <a class="conversation-list-item {{ $conversation['id'] === $selected['id'] ? 'is-active' : '' }}" href="{{ route('conversations.index', ['conversation' => $conversation['id']]) }}" data-conversation-item data-type="{{ $conversation['type'] }}" data-unread="{{ $conversation['unread'] }}" data-search-text="{{ $conversation['title'] }} {{ $conversation['preview'] }}">
                        <span class="conversation-avatar conversation-avatar--{{ $conversation['type'] }}">{{ $conversation['initials'] }}</span>
                        <span class="conversation-list-item__copy"><span><strong>{{ $conversation['title'] }}</strong><time>{{ $conversation['updated'] }}</time></span><small>{{ $conversation['preview'] }}</small></span>
                        @if ($conversation['unread'] > 0)<b>{{ $conversation['unread'] }}</b>@endif
                    </a>
                @endforeach
            </div>
        </aside>

        <main class="conversation-thread conversation-thread--{{ $selected['type'] }}">
            <header class="conversation-thread__header {{ $selected['type'] === 'direct' ? 'conversation-thread__header--patient' : '' }}">
                <span class="conversation-avatar conversation-avatar--{{ $selected['type'] }}">{{ $selected['initials'] }}</span>
                @if ($selected['type'] === 'direct')
                    <div class="conversation-patient-copy">
                        <h2><a href="{{ route('patients.show', $selected['patient_user_id']) }}">{{ $selected['title'] }}</a></h2>
                        <p>HN {{ $selected['patient']['hn'] }} • อายุ {{ $selected['patient']['age'] }} ปี • อายุครรภ์ {{ $selected['patient']['gestational_age'] }}</p>
                        <a class="conversation-patient-link" href="{{ route('patients.show', $selected['patient_user_id']) }}"><i class="fa-regular fa-address-card" aria-hidden="true"></i> ดูหน้าผู้รับบริการ <span aria-hidden="true">→</span></a>
                    </div>
                    <div class="conversation-patient-summary">
                        <span class="conversation-patient-summary__icon" aria-hidden="true"><i class="fa-solid fa-heart-pulse"></i></span>
                        <div><strong>ข้อมูลผู้รับบริการ</strong><p>{{ $selected['patient']['clinical_summary'] }}</p></div>
                    </div>
                @else
                    <div><h2>{{ $selected['title'] }}</h2><p>{{ $selected['subtitle'] }}</p></div>
                @endif
            </header>
            <label class="conversation-thread-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span class="sr-only">ค้นหาในบทสนทนา</span><input type="search" placeholder="ค้นหาในบทสนทนา" data-message-search></label>
            <section class="conversation-messages" aria-label="ข้อความในบทสนทนา" aria-live="polite" data-conversation-messages>
                <p class="conversation-date">หน้า {{ $conversationPagination['current_page'] ?? 1 }} จาก {{ $conversationPagination['last_page'] ?? 1 }}</p>
                @forelse ($selected['messages'] as $message)
                        <article class="conversation-message conversation-message--{{ $message['direction'] }} conversation-message--{{ $message['tone'] }}" data-message data-search-text="{{ $message['sender'] }} {{ $message['body'] }}">
                            <p class="conversation-message__meta">{{ $message['sender'] }} • {{ $message['time'] }}</p>
                            <p>{{ $message['body'] }}</p>
                        </article>
                @empty
                    <p class="conversation-date">ไม่พบข้อความตามเงื่อนไขที่ค้นหา</p>
                @endforelse
            </section>

            @if (($conversationPagination['last_page'] ?? 1) > 1)
                <nav class="conversation-pagination" aria-label="หน้าประวัติสนทนา">
                    @if ($conversationPagination['current_page'] > 1)
                        <a href="{{ route('conversations.index', array_merge(request()->except('page'), ['page' => $conversationPagination['current_page'] - 1])) }}">← ข้อความใหม่กว่า</a>
                    @else
                        <span></span>
                    @endif
                    <span>{{ $conversationPagination['current_page'] }} / {{ $conversationPagination['last_page'] }}</span>
                    @if ($conversationPagination['current_page'] < $conversationPagination['last_page'])
                        <a href="{{ route('conversations.index', array_merge(request()->except('page'), ['page' => $conversationPagination['current_page'] + 1])) }}">ข้อความเก่ากว่า →</a>
                    @endif
                </nav>
            @endif

            <form class="conversation-composer" data-conversation-form data-conversation-type="{{ $selected['type'] }}" data-conversation-target="{{ $selected['type'] === 'direct' ? $selected['patient_user_id'] : 'doctor-patient-cohort' }}">
                <label class="sr-only" for="conversation-message">พิมพ์ข้อความ</label>
                <input id="conversation-message" name="message" type="text" maxlength="255" placeholder="พิมพ์ข้อความเพื่อส่งผ่าน LINE" autocomplete="off" required>
                <button type="submit">ส่ง</button>
                <p class="conversation-composer__status" data-conversation-status aria-live="polite"></p>
            </form>
        </main>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.11/flatpickr.min.css">
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.11/flatpickr.min.js" defer></script>
@endpush
