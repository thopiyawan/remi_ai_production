@extends('layouts.auth')

@section('title', 'เข้าสู่ระบบ | REMI')

@section('content')
<main class="login-page">
    <section class="login-brand-panel" aria-labelledby="remi-tagline">
        <x-auth.brand />

        <div class="login-brand-content">
            <h1 id="remi-tagline">ผู้ช่วยติดตามสุขภาพครรภ์<br>สำหรับสูติแพทย์</h1>
            <p>ติดตามสุขภาพคุณแม่และทารกแบบเรียลไทม์<br>สนับสนุนการวินิจฉัยและวางแผนดูแลเฉพาะบุคคล</p>
        </div>

        <x-auth.collaboration class="login-brand-partners" />
    </section>

    <section class="login-form-panel" aria-labelledby="login-heading">
        <div class="login-form-container">
            <p class="login-eyebrow">REMI Login</p>
            <h2 id="login-heading">ยินดีต้อนรับ</h2>
            <p class="login-description">เข้าสู่ระบบเพื่อจัดการข้อมูลสุขภาพแม่ตั้งครรภ์</p>

            @if (session('message') || isset($message))
                <div class="auth-alert" role="alert">{{ session('message') ?? $message }}</div>
            @endif

            <form class="login-form" method="POST" action="{{ route('login') }}">
                @csrf

                <x-ui.input id="doctor_id" name="doctor_id" label="รหัสประจำตัวแพทย์" placeholder="กรอกรหัสประจำตัวแพทย์" value="{{ old('doctor_id') }}" autocomplete="username" :autofocus="true" />

                <x-ui.input id="password" name="password" label="รหัสผ่าน" type="password" placeholder="••••••••" autocomplete="current-password" />

                <div class="login-form__help">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">ลืมรหัสผ่าน?</a>
                    @else
                        <span>ลืมรหัสผ่าน?</span>
                    @endif
                </div>

                <x-ui.button type="submit" :block="true">เข้าสู่ระบบ</x-ui.button>
            </form>

            <p class="login-notice">ระบบสำหรับบุคลากรทางการแพทย์ที่ได้รับอนุญาตเท่านั้น</p>
            <x-auth.collaboration class="login-mobile-partners" />
        </div>
    </section>
</main>
@endsection
