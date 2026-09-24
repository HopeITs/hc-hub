@props([
    'format',
    'city',
    'program',
    'jobTitle',
    'id' => null,
])

@php
    $isPost = $format === 'post';
    $viewBox = $isPost ? '0 0 1000 1000' : '0 0 1080 1920';
    $exportWidth = 2160;
    $exportHeight = $isPost ? 2160 : 3840;
    $asset = fn (string $name): string => asset("assets/recruitment/hope-center/{$name}.svg");
@endphp

<svg
    @if ($id) id="{{ $id }}" @endif
    {{ $attributes->class('hc-job-post-svg') }}
    xmlns="http://www.w3.org/2000/svg"
    viewBox="{{ $viewBox }}"
    width="{{ $isPost ? 1000 : 1080 }}"
    height="{{ $isPost ? 1000 : 1920 }}"
    data-export-width="{{ $exportWidth }}"
    data-export-height="{{ $exportHeight }}"
    data-export-format="{{ $format }}"
    role="img"
    aria-label="{{ __('recruitment.job_post.preview_aria', ['format' => $format]) }}"
    style="direction:ltr"
>
    <rect width="100%" height="100%" fill="#be1f24" />

    @if ($isPost)
        <image href="{{ $asset('logo') }}" x="182.031" y="122.85" width="332" height="111" />
        <image href="{{ $asset('hiring') }}" x="182" y="333" width="645.651" height="307.14" />
        <image href="{{ $asset('arrow2') }}" x="750" y="810" width="65.026" height="65.849" />
        <image href="{{ $asset('loc') }}" x="182" y="750.85" width="13.472" height="20.051" />
        <text direction="ltr" text-anchor="start" x="203" y="771.85" font-family="SomarSans" font-weight="900" font-size="28.48" fill="#fff">{{ mb_strtoupper($city) }}</text>
        <text direction="ltr" text-anchor="start" x="182" y="819.85" font-family="SomarSans" font-weight="400" font-size="28.588" fill="#fff">{{ mb_strtoupper($program) }}</text>
        <text direction="ltr" text-anchor="start" x="182" y="875.85" font-family="SomarSans" font-weight="700" font-size="40" fill="#fff">{{ $jobTitle }}</text>
    @else
        <image href="{{ $asset('logo') }}" x="152.5" y="231.385" width="480.133" height="164.738" />
        <image href="{{ $asset('hiring') }}" x="152.5" y="699" width="775.281" height="364.503" />
        <image href="{{ $asset('arrow') }}" x="840" y="1545" width="85.598" height="86.61" />
        <image href="{{ $asset('line') }}" x="152.5" y="1682" width="775.281" height="9.677" preserveAspectRatio="none" />
        <image href="{{ $asset('loc') }}" x="152.5" y="1480.6" width="16.347" height="24.33" />
        <text direction="ltr" text-anchor="start" x="180" y="1506.6" font-family="SomarSans" font-weight="900" font-size="34.559" fill="#fff">{{ mb_strtoupper($city) }}</text>
        <text direction="ltr" text-anchor="start" x="152.5" y="1565.6" font-family="SomarSans" font-weight="400" font-size="34.69" fill="#fff">{{ mb_strtoupper($program) }}</text>
        <text direction="ltr" text-anchor="start" x="152.5" y="1631.6" font-family="SomarSans" font-weight="700" font-size="47" fill="#fff">{{ $jobTitle }}</text>
    @endif
</svg>
