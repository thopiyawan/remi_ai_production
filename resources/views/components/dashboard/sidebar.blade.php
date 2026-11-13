@props([
    'doctorName' => '',
    'doctorId' => '',
    'doctorHospital' => '',
    'doctorQrCode' => '',
])

<aside class="dashboard-sidebar" aria-label="เมนูหลัก">
    <a class="dashboard-brand" href="{{ route('dashboard') }}" aria-label="REMI ภาพรวม">
        <img src="{{ asset('css/remi.png') }}" alt="" width="35" height="35">
        <span>REMI</span>
    </a>

    <nav class="dashboard-navigation">
        <a class="dashboard-navigation__item {{ request()->routeIs('dashboard') ? 'dashboard-navigation__item--active' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
            <i class="fa-solid fa-house-medical" aria-hidden="true"></i>
            <span>ภาพรวม</span>
        </a>
        <a class="dashboard-navigation__item {{ request()->routeIs('patients.*') ? 'dashboard-navigation__item--active' : '' }}" href="{{ route('patients.index') }}" @if(request()->routeIs('patients.*')) aria-current="page" @endif>
            <i class="fa-solid fa-user" aria-hidden="true"></i>
            <span>ผู้รับบริการ</span>
        </a>
        <a class="dashboard-navigation__item {{ request()->routeIs('nutrition-review.*') ? 'dashboard-navigation__item--active' : '' }}" href="{{ route('nutrition-review.index') }}" @if(request()->routeIs('nutrition-review.*')) aria-current="page" @endif>
            <i class="fa-solid fa-bolt" aria-hidden="true"></i>
            <span>ตรวจอาหาร</span>
        </a>
        <a class="dashboard-navigation__item {{ request()->routeIs('conversations.*') ? 'dashboard-navigation__item--active' : '' }}" href="{{ route('conversations.index') }}" @if(request()->routeIs('conversations.*')) aria-current="page" @endif>
            <i class="fa-solid fa-comment" aria-hidden="true"></i>
            <span>สนทนา</span>
        </a>
    </nav>

    <div class="dashboard-doctor" data-doctor-menu>
        <button
            class="dashboard-doctor__trigger"
            type="button"
            aria-label="เปิดเมนูบัญชีแพทย์"
            aria-expanded="false"
            aria-controls="doctor-account-menu"
            data-doctor-menu-toggle
        >
            <x-dashboard.doctor-avatar :name="$doctorName" />
        </button>

        <div class="doctor-account-menu" id="doctor-account-menu" role="menu" hidden data-doctor-menu-panel>
            <div class="doctor-account-menu__profile">
                <x-dashboard.doctor-avatar :name="$doctorName" />
                <span>
                    <small>แพทย์เจ้าของบัญชี</small>
                    <strong>{{ $doctorName ?: 'ไม่พบชื่อแพทย์' }}</strong>
                    @if($doctorHospital)
                        <span>{{ $doctorHospital }}</span>
                    @endif
                    @if($doctorId)
                        <span>รหัสแพทย์ {{ $doctorId }}</span>
                    @endif
                </span>
            </div>

            <button class="doctor-account-menu__action" type="button" role="menuitem" data-doctor-qr-open>
                <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                <span>แสดง QR Code</span>
            </button>

            <a class="doctor-account-menu__action doctor-account-menu__action--logout" href="{{ route('logout') }}" role="menuitem">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                <span>ลงชื่อออก</span>
            </a>
        </div>
    </div>

    <div class="doctor-qr-modal" role="dialog" aria-modal="true" aria-labelledby="doctor-qr-title" hidden data-doctor-qr-modal>
        <button class="doctor-qr-modal__backdrop" type="button" aria-label="ปิดหน้าต่าง QR Code" data-doctor-qr-close></button>
        <section class="doctor-qr-modal__dialog">
            <button class="doctor-qr-modal__close" type="button" aria-label="ปิด" data-doctor-qr-close>
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
            <h2 id="doctor-qr-title">QR Code สำหรับเชื่อมต่อแพทย์</h2>
            <p>{{ $doctorName ?: 'แพทย์เจ้าของบัญชี' }}</p>
            @if($doctorQrCode)
                <img src="{{ $doctorQrCode }}" alt="QR Code สำหรับเชื่อมต่อกับ {{ $doctorName ?: 'แพทย์' }}">
                <small>ให้ผู้รับบริการสแกน QR Code นี้เพื่อเชื่อมต่อบัญชี</small>
            @else
                <div class="doctor-qr-modal__empty">
                    <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                    <span>ยังไม่พบ QR Code ของบัญชีนี้</span>
                </div>
            @endif
        </section>
    </div>
</aside>
