<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />


<!-- Favicon -->
<link rel="icon" href="{{ asset('assets/img/favicon.png') }}" type="image/x-icon">
<link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}" type="image/x-icon">



<!-- Feather CSS -->
<link rel="stylesheet" href="{{ asset('assets/plugins/icons/feather/feather.css') }}">

<!-- Tabler Icon CSS -->
<link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.css') }}">

<!-- Select2 CSS -->
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">

<!-- Fontawesome CSS -->
<link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">

<!-- Datetimepicker CSS -->
<link rel="stylesheet" href="{{ asset('assets/plugins/flatpickr/flatpickr.min.css') }}">

<!-- Bootstrap Tagsinput CSS -->
<link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css') }}">

<!-- Summernote CSS -->
<link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-lite.min.css') }}">

<!-- Daterangepikcer CSS -->
<link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">

<!-- Flowbite CSS -->
<link rel="stylesheet" href="{{ asset('assets/css/flowbite.min.css') }}">

<!-- Color Picker Css -->
<link rel="stylesheet" href="{{ asset('assets/plugins/flatpickr/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/@simonwep/pickr/themes/nano.min.css') }}">

<!-- Main CSS -->
<link rel="stylesheet" href="{{asset('assets/dist/style.css')}}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
