@props(['chat'])

<div class="chat-widget" data-chat-widget data-unread-count="{{ $chat['unread_count'] }}" data-thread-url="{{ route('conversations.thread') }}" data-send-url="{{ route('conversations.messages.store') }}">
    <section class="chat-panel" data-chat-panel aria-label="หน้าต่างสนทนา" aria-hidden="true">
        <header class="chat-panel__header">
            <button class="chat-panel__back" type="button" data-chat-back aria-label="กลับไปยังรายการสนทนา" hidden>←</button>
            <span class="chat-panel__team-avatar" data-chat-avatar>แชท</span>
            <div><h2 data-chat-title>{{ $chat['title'] }}</h2><p data-chat-subtitle>{{ $chat['subtitle'] ?? 'เลือกบทสนทนา' }}</p></div>
            <button type="button" data-chat-minimize aria-label="ย่อหน้าต่างสนทนา">−</button>
            <button type="button" data-chat-close aria-label="ปิดหน้าต่างสนทนา">×</button>
        </header>
        <nav class="chat-panel__tabs" aria-label="ประเภทข้อความ" data-chat-tabs>
            <button type="button" class="is-active" aria-current="page" data-chat-filter="direct">Direct</button>
            <button type="button" data-chat-filter="group">กลุ่ม</button>
            <a href="{{ route('conversations.index') }}">ประวัติทั้งหมด ↗</a>
        </nav>
        <div class="chat-conversation-list" data-chat-list aria-label="รายการสนทนา">
            @forelse (($chat['conversations'] ?? []) as $conversation)
                <button type="button" class="chat-conversation-item" data-chat-conversation data-type="{{ $conversation['type'] }}" data-conversation-id="{{ $conversation['id'] }}" data-target-id="{{ $conversation['target_id'] }}" data-title="{{ $conversation['title'] }}" data-subtitle="{{ $conversation['subtitle'] }}" data-initials="{{ $conversation['initials'] }}">
                    <span class="conversation-avatar conversation-avatar--{{ $conversation['type'] }}">{{ $conversation['initials'] }}</span>
                    <span><strong>{{ $conversation['title'] }}</strong><small>{{ $conversation['preview'] }}</small></span>
                    <time>{{ $conversation['updated'] }}</time>
                    @if ($conversation['unread'] > 0)<b>{{ $conversation['unread'] }}</b>@endif
                </button>
            @empty
                <p class="chat-panel__empty">ยังไม่มีรายการสนทนา</p>
            @endforelse
        </div>
        <div class="chat-panel__messages" data-chat-messages aria-live="polite" hidden>
        </div>
        <form class="chat-composer" data-chat-form hidden>
            <label class="sr-only" for="chat-message-input">พิมพ์ข้อความ</label>
            <input id="chat-message-input" name="message" type="text" maxlength="255" placeholder="พิมพ์ข้อความเพื่อส่งผ่าน LINE" autocomplete="off" required>
            <button type="submit">ส่ง</button>
            <p data-chat-status aria-live="polite">ข้อความจะถูกส่งผ่าน LINE และบันทึกในประวัติ</p>
        </form>
    </section>

    <button class="chat-launcher" type="button" data-chat-toggle aria-label="เปิดหน้าต่างสนทนา" aria-expanded="false">
        <i class="fa-regular fa-comments" aria-hidden="true"></i>
        @if ($chat['unread_count'] > 0)
            <span class="chat-launcher__badge" data-chat-badge>{{ $chat['unread_count'] }}</span>
        @endif
    </button>
</div>
