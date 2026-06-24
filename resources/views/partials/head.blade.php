<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="apple-touch-icon" sizes="180x180" href="{{asset('favicons/apple-touch-icon.png')}}">
<link rel="icon" type="image/png" sizes="32x32" href="{{asset('favicons/favicon-32x32.png')}}">
<link rel="icon" type="image/png" sizes="16x16" href="{{asset('favicons/favicon-16x16.png')}}">
<link rel="manifest" href="{{asset('favicons/site.webmanifest')}}">



{{-- Preload the critical brand font weights so text paints without a flash --}}
<link rel="preload" href="{{ asset('fonts/SomarSans/SomarSans-Regular.otf') }}" as="font" type="font/otf" crossorigin>
<link rel="preload" href="{{ asset('fonts/SomarSans/SOMARSANS-MEDIUM.OTF') }}" as="font" type="font/otf" crossorigin>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" integrity="sha512-DxV+EoADOkOygM4IR9yXP8Sb2qwgidEmeqAEmDKIOfPRQZOWbXCzLC6vjbZyy0vPisbH2SyW27+ddLVCN+OMzQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

@yield('css')
@livewireStyles
@vite(['resources/js/app.js'])
