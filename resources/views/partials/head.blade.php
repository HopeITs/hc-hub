<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="{{ asset('assets/img/hope-center-icon.svg') }}?v=20260615" type="image/svg+xml">
<link rel="shortcut icon" href="{{ asset('assets/img/hope-center-icon.svg') }}?v=20260615" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

{{-- Preload the critical brand font weights so text paints without a flash --}}
<link rel="preload" href="{{ asset('fonts/SomarSans/SomarSans-Regular.otf') }}" as="font" type="font/otf" crossorigin>
<link rel="preload" href="{{ asset('fonts/SomarSans/SOMARSANS-MEDIUM.OTF') }}" as="font" type="font/otf" crossorigin>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" integrity="sha512-DxV+EoADOkOygM4IR9yXP8Sb2qwgidEmeqAEmDKIOfPRQZOWbXCzLC6vjbZyy0vPisbH2SyW27+ddLVCN+OMzQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

@yield('css')
@livewireStyles
@vite(['resources/js/app.js'])
