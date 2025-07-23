<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">



<!-- Favicon -->
<link rel="icon" href="{{ asset('assets/img/favicon.png') }}" type="image/x-icon">
<link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}" type="image/x-icon">
<!-- Tabler Icon CSS -->
<link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.css') }}">


<!-- Feather CSS -->
<link rel="stylesheet" href="{{ asset('assets/plugins/icons/feather/feather.css') }}">


<!-- Fontawesome CSS -->
<link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">


<!-- Flowbite CSS -->
<link rel="stylesheet" href="{{ asset('assets/css/flowbite.min.css') }}">

<!-- Color Picker Css -->
<link rel="stylesheet" href="{{ asset('assets/plugins/flatpickr/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/@simonwep/pickr/themes/nano.min.css') }}">

<!-- Main CSS -->
<link rel="stylesheet" href="{{ asset('assets/dist/style.css') }}">
@vite(['resources/js/app.js'])
@fluxAppearance
@livewireStyles
