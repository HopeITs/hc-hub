@props([
  'en' => '',
  'ar' => '',
  'enClass' => '',
  'arClass' => '',
  'rowClass' => '',
  'tight' => false,
])

@php
    $rowClasses = trim('dual-row '.($rowClass ?: '').($tight ? ' dual-tight' : ''));
    $enClasses  = trim('dual-en leading-7 text-[15px] '.($enClass ?: ''));
    $arClasses  = trim('dual-ar leading-7 text-[15px] '.($arClass ?: ''));
@endphp

<div {{ $attributes->merge(['class' => $rowClasses]) }}>
    <div class="{{ $enClasses }}" dir="ltr">{!! $en !!}</div>
    <div class="{{ $arClasses }}" dir="rtl">{!! $ar !!}</div>
</div>
