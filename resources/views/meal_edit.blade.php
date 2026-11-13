<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#df3f78">
    <title>แก้ไขมื้ออาหาร</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/liff-meals.css') }}">
</head>
<body class="liff-meal-page">
@php
    $thaiMonthsShort = [1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $date = \Carbon\Carbon::parse($activity->date);
    $thaiDate = $date->day . ' ' . $thaiMonthsShort[$date->month] . ' ' . ($date->year + 543);
    $time = $activity->time ? \Carbon\Carbon::parse($activity->time)->format('H:i') : now('Asia/Bangkok')->format('H:i');
    $unitLabels = [1 => 'ทัพพี', 2 => 'ช้อน', 3 => 'ช้อนโต๊ะ', 4 => 'ลูก', 5 => 'ฟอง', 6 => 'ตัว', 7 => 'มล.', 8 => 'ชิ้น', 9 => 'อื่นๆ'];
    $displayUnit = $unitLabels[(int) $activity->unit] ?? ($activity->unit ?: 'g');
@endphp

<main class="meal-app-shell meal-app-shell--edit">
    <header class="meal-topbar">
        <a class="meal-icon-link" href="{{ route('food-diary.index', ['id' => $user, 'date' => $activity->date]) }}" aria-label="ย้อนกลับ"><i class="fa-solid fa-chevron-left"></i></a>
        <div class="meal-topbar__title">
            <h1>แก้ไขมื้ออาหาร</h1>
            <p>มื้อ{{ $mealMeta['label'] }}</p>
        </div>
        <form class="meal-topbar__delete" method="POST" action="{{ route('food-diary.destroy', ['user' => $user, 'activity' => $activity->id]) }}" onsubmit="return confirm('ต้องการลบรายการอาหารนี้ใช่หรือไม่?')">
            @csrf
            @method('DELETE')
            <button type="submit">ลบ</button>
        </form>
    </header>

    <form class="meal-edit-form" method="POST" action="{{ route('food-diary.update', ['user' => $user, 'activity' => $activity->id]) }}">
        @csrf
        @method('PATCH')

        <img class="meal-edit-hero" src="{{ asset($activity->image_path ?: $mealMeta['image']) }}" alt="{{ $mealName }}">

        <div class="meal-meta-grid">
            <div><span>วันที่</span><strong>{{ $thaiDate }}</strong></div>
            <label><span>เวลา</span><input type="time" name="time" value="{{ old('time', $time) }}" required></label>
            <div><span>มื้ออาหาร</span><strong>{{ $mealMeta['label'] }}</strong></div>
        </div>

        <label class="meal-field meal-field--wide">
            <span>ชื่ออาหาร</span>
            <input type="text" name="food_name" value="{{ old('food_name', $mealName) }}" required>
        </label>
        @error('food_name')<p class="meal-field-error">{{ $message }}</p>@enderror

        <div class="meal-double-fields">
            <label class="meal-field">
                <span>ปริมาณ</span>
                <div><input type="number" inputmode="decimal" min="0" step="0.01" name="portion" value="{{ old('portion', $activity->portion) }}"><b>{{ $displayUnit }}</b></div>
            </label>
            <label class="meal-field meal-field--calorie">
                <span>พลังงาน</span>
                <div><input type="number" inputmode="decimal" min="0" step="0.01" name="calorie" value="{{ old('calorie', $activity->calorie) }}"><b>kcal</b></div>
            </label>
        </div>
        <input type="hidden" name="unit" value="{{ old('unit', $activity->unit) }}">

        <fieldset class="meal-nutrition-fields">
            <legend>คุณค่าทางโภชนาการ</legend>
            @foreach([
                'protein' => ['โปรตีน', 'green'],
                'carbohydrate' => ['คาร์บ', 'coral'],
                'fat' => ['ไขมัน', 'pink'],
                'fiber' => ['ใยอาหาร', 'mint'],
            ] as $field => [$label, $tone])
                <label class="meal-nutrition-row meal-nutrition-row--{{ $tone }}">
                    <span><i></i>{{ $label }}</span>
                    <div><input type="number" inputmode="decimal" min="0" step="0.01" name="{{ $field }}" value="{{ old($field, $activity->{$field}) }}"><b>g</b></div>
                </label>
            @endforeach
        </fieldset>

        @if($errors->any())
            <div class="meal-form-errors" role="alert">กรุณาตรวจสอบข้อมูลที่กรอกอีกครั้ง</div>
        @endif

        <div class="meal-edit-submit-wrap">
            <button type="submit">บันทึกการแก้ไข</button>
        </div>
    </form>
</main>
</body>
</html>
