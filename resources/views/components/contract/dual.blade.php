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
    $enClasses  = trim('dual-en '.($enClass ?: ''));
    $arClasses  = trim('dual-ar '.($arClass ?: ''));
@endphp

<div {{ $attributes->merge(['class' => $rowClasses]) }}>
    <div class="{{ $enClasses }}" dir="ltr" lang="en">{!! $en !!}</div>
    <div class="{{ $arClasses }}" style="direction: rtl" dir="rtl" lang="ar">{!! $ar !!}</div>
</div>
