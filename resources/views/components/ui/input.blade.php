@props([
    'id',
    'label',
    'name' => null,
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
    'autocomplete' => null,
    'autofocus' => false,
    'required' => true,
])

@php($fieldName = $name ?? $id)

<div {{ $attributes->only('class')->class(['ui-field']) }}>
    <label class="ui-field__label" for="{{ $id }}">{{ $label }}</label>
    <input
        class="ui-field__control @error($fieldName) ui-field__control--invalid @enderror"
        id="{{ $id }}"
        name="{{ $fieldName }}"
        type="{{ $type }}"
        placeholder="{{ $placeholder }}"
        @if(! is_null($value)) value="{{ $value }}" @endif
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if($autofocus) autofocus @endif
        @if($required) required @endif
        {{ $attributes->except('class') }}
    >
    @error($fieldName)
        <p class="ui-field__error" role="alert">{{ $message }}</p>
    @enderror
</div>
