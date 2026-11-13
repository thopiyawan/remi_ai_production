@props([
    'name',
    'options' => [],
    'value' => null,
    'id' => null,
    'label' => null,
    'placeholder' => 'เลือกข้อมูล',
    'autoSubmit' => false,
    'required' => false,
])

@php
    $selectId = $id ?: 'ui-select-' . \Illuminate\Support\Str::uuid();
    $normalizedOptions = collect($options)->map(function ($optionLabel, $optionValue) {
        return is_int($optionValue)
            ? ['value' => (string) $optionLabel, 'label' => (string) $optionLabel]
            : ['value' => (string) $optionValue, 'label' => (string) $optionLabel];
    })->values();
    $selectedOption = $normalizedOptions->first(fn ($option) => (string) $option['value'] === (string) $value);
@endphp

<div {{ $attributes->class(['ui-select-field']) }}>
    @if($label)<label class="ui-select-field__label" for="{{ $selectId }}-toggle"><span class="ui-field-label-text">{{ $label }}@if($required)<span class="ui-required-mark" aria-hidden="true">*</span><span class="sr-only"> จำเป็น</span>@endif</span></label>@endif
    <div class="ui-select" data-ui-select>
        <select
            class="ui-select__native sr-only"
            id="{{ $selectId }}"
            name="{{ $name }}"
            tabindex="-1"
            aria-hidden="true"
            data-ui-select-native
            @if($required) required @endif
            @if($autoSubmit) data-auto-submit-select @endif
        >
            @foreach($normalizedOptions as $option)
                <option value="{{ $option['value'] }}" @selected((string) $option['value'] === (string) $value)>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <button class="ui-select__toggle" id="{{ $selectId }}-toggle" type="button" data-ui-select-toggle aria-haspopup="listbox" aria-expanded="false">
            <span data-ui-select-label>{{ $selectedOption['label'] ?? $placeholder }}</span>
            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
        </button>
        <div class="ui-select__menu" role="listbox" aria-label="{{ $label ?: $placeholder }}">
            @foreach($normalizedOptions as $option)
                <button type="button" role="option" aria-selected="{{ (string) $option['value'] === (string) $value ? 'true' : 'false' }}" data-ui-select-option data-value="{{ $option['value'] }}">{{ $option['label'] }}</button>
            @endforeach
        </div>
    </div>
</div>
