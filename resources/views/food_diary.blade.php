<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#df3f78">
    <title>ประวัติการรับประทานอาหาร</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/liff-meals.css') }}">
</head>
<body class="liff-meal-page">
@php
    $thaiMonths = [1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    $thaiDate = $thaiDays[$selectedDate->dayOfWeek] . ' ' . $selectedDate->day . ' ' . $thaiMonths[$selectedDate->month] . ' ' . ($selectedDate->year + 543);
    $targets = ['carbohydrate' => 250, 'protein' => 75, 'fat' => 65, 'fiber' => (float) config('dashboard.fiber_target_g', 25)];
    $macroConfig = [
        'carbohydrate' => ['label' => 'คาร์บ', 'class' => 'coral'],
        'protein' => ['label' => 'โปรตีน', 'class' => 'green'],
        'fat' => ['label' => 'ไขมัน', 'class' => 'pink'],
        'fiber' => ['label' => 'ใยอาหาร', 'class' => 'mint'],
    ];
    $groups = $meals->groupBy('meal');
    $mealOrder = [1, 4, 2, 5, 3];
    $caloriePercent = min(100, (int) round(($summary['calorie'] / 2300) * 100));
@endphp

<main class="meal-app-shell">
    <header class="meal-topbar">
        <a class="meal-icon-link" href="{{ url('record_diary/' . $id) }}" aria-label="ย้อนกลับ"><i class="fa-solid fa-chevron-left"></i></a>
        <div class="meal-topbar__title">
            <h1>ประวัติการรับประทานอาหาร</h1>
            <p>รายการอาหารที่รับประทาน</p>
        </div>
        <a class="meal-today-link" href="{{ route('food-diary.index', ['id' => $id, 'date' => now('Asia/Bangkok')->toDateString()]) }}">วันนี้</a>
    </header>

    @if(session('message'))
        <div class="meal-toast" role="status"><i class="fa-solid fa-circle-check"></i>{{ session('message') }}</div>
    @endif

    <nav class="meal-date-nav" aria-label="เลือกวัน">
        <a href="{{ route('food-diary.index', ['id' => $id, 'date' => $selectedDate->copy()->subDay()->toDateString()]) }}" aria-label="วันก่อนหน้า"><i class="fa-solid fa-chevron-left"></i></a>
        <strong>{{ $thaiDate }}</strong>
        <a href="{{ route('food-diary.index', ['id' => $id, 'date' => $selectedDate->copy()->addDay()->toDateString()]) }}" aria-label="วันถัดไป"><i class="fa-solid fa-chevron-right"></i></a>
    </nav>

    <section class="meal-summary-card" aria-label="สรุปพลังงานและสารอาหาร">
        <div class="meal-energy-row">
            <div>
                <p>พลังงานที่ได้รับวันนี้</p>
                <strong>{{ number_format($summary['calorie']) }}</strong>
                <span>จากเป้าหมาย 2,300 kcal</span>
            </div>
            <div class="meal-energy-ring" style="--progress: {{ $caloriePercent * 3.6 }}deg">
                <strong>{{ $caloriePercent }}%</strong>
                <span>ครบแล้ว</span>
            </div>
        </div>
        <div class="meal-macros">
            @foreach($macroConfig as $key => $config)
                @php $percent = min(100, (int) round(($summary[$key] / $targets[$key]) * 100)); @endphp
                <div class="meal-macro meal-macro--{{ $config['class'] }}">
                    <span>{{ $config['label'] }}</span>
                    <strong>{{ $percent }}%</strong>
                    <i><b style="width: {{ $percent }}%"></b></i>
                </div>
            @endforeach
        </div>
    </section>

    <div class="meal-list-heading">
        <h2>รายการอาหาร 5 มื้อ</h2>
        <span>{{ $meals->count() }} รายการ • {{ number_format($summary['calorie']) }} kcal</span>
    </div>

    <section class="meal-history-list">
        @foreach($mealOrder as $mealNumber)
            @if($groups->has($mealNumber))
                @php $firstMeal = $groups[$mealNumber]->first(); @endphp
                <section class="meal-period meal-period--{{ $firstMeal->tone }}">
                    <header>
                        <div><i></i><strong>{{ $firstMeal->meal_label }} • {{ $firstMeal->time }} น.</strong></div>
                        <div><span>{{ number_format($groups[$mealNumber]->sum('calorie')) }} kcal</span><a href="{{ url('record_diary/' . $id) }}?meal={{ $mealNumber }}&amp;date={{ $selectedDate->toDateString() }}">＋ เพิ่ม</a></div>
                    </header>
                    @foreach($groups[$mealNumber] as $meal)
                        <article class="meal-history-item">
                            <img src="{{ asset($meal->image) }}" alt="{{ $meal->food_name }}">
                            <div class="meal-history-item__body">
                                <strong>{{ $meal->food_name }}</strong>
                                <span>ปริมาณ {{ rtrim(rtrim(number_format($meal->portion, 2), '0'), '.') }} {{ $meal->unit }}</span>
                            </div>
                            <b>{{ number_format($meal->calorie) }} kcal</b>
                            <div class="meal-history-item__actions">
                                <a href="{{ route('food-diary.edit', ['user' => $id, 'activity' => $meal->id]) }}" aria-label="แก้ไข {{ $meal->food_name }}"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="{{ route('food-diary.destroy', ['user' => $id, 'activity' => $meal->id]) }}" onsubmit="return confirm('ต้องการลบรายการอาหารนี้ใช่หรือไม่?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="ลบ {{ $meal->food_name }}"><i class="fa-regular fa-trash-can"></i></button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </section>
            @endif
        @endforeach

        @if($meals->isEmpty())
            <div class="meal-empty-state">
                <i class="fa-solid fa-bowl-food"></i>
                <h2>ยังไม่มีรายการอาหารในวันนี้</h2>
                <p>เพิ่มมื้ออาหารเพื่อเริ่มติดตามพลังงานและสารอาหาร</p>
                <a href="{{ url('record_diary/' . $id) }}?meal=1&amp;date={{ $selectedDate->toDateString() }}">เพิ่มรายการอาหาร</a>
            </div>
        @endif
    </section>
</main>
</body>
</html>
