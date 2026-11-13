@props(['patientId', 'active' => 'overview'])

@php
    $tabs = [
        ['key' => 'overview', 'label' => 'ภาพรวม', 'route' => 'patients.show'],
        ['key' => 'health', 'label' => 'น้ำหนัก ออกกำลังกาย ลูกดิ้น', 'route' => 'patients.health'],
        ['key' => 'nutrition', 'label' => 'อาหาร', 'route' => 'patients.nutrition'],
        ['key' => 'glucose', 'label' => 'น้ำตาลในเลือด', 'route' => 'patients.glucose'],
    ];
@endphp

<nav class="patient-section-tabs" aria-label="ข้อมูลผู้รับบริการ">
    @foreach ($tabs as $tab)
        <a
            class="patient-section-tab {{ $active === $tab['key'] ? 'patient-section-tab--active' : '' }}"
            href="{{ route($tab['route'], $patientId) }}"
            @if($active === $tab['key']) aria-current="page" @endif
        >{{ $tab['label'] }}</a>
    @endforeach
</nav>
