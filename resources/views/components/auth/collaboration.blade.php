<aside {{ $attributes->class(['collaboration-card']) }} aria-label="ภายใต้ความร่วมมือ">
    <p class="collaboration-card__title">ภายใต้ความร่วมมือ</p>
    <div class="collaboration-card__logos">
        @foreach (range(1, 6) as $partner)
            <img
                class="collaboration-card__logo collaboration-card__logo--{{ $partner }}"
                src="{{ asset('sponsor_logo/logo_'.$partner.($partner === 1 ? '.jpg' : '.png')) }}"
                alt="โลโก้หน่วยงานพันธมิตร {{ $partner }}"
            >
        @endforeach
    </div>
</aside>
