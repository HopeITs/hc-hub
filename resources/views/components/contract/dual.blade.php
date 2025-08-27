@props([
  'en' => '',
  'ar' => '',
  'enClass' => '',
  'arClass' => '',
  'rowClass' => '',
  'tight' => true,
])

@php
    $row = trim(($rowClass ?: '') . ' ' . ($tight ? 'tight' : ''));
@endphp

<div {{ $attributes->merge(['class' => "dual-row {$row}"]) }}>
    <div class="dual-col dual-en leading-7 text-[15px] {{ $enClass }}" dir="ltr">
        {!! $en !!}
    </div>

    <div class="dual-col dual-ar leading-7 text-[15px] {{ $arClass }}" dir="rtl">
        {!! $ar !!}
    </div>
</div>
