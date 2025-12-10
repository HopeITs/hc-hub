@extends('errors.layout')

@section('title', '500 Server Error')

@section('page-background')
    <style>
        body {
            background: url('{{ asset('assets/errors/404.png') }}') no-repeat center center;
            background-size: cover;
            position: relative;
            top:0;
        }
    </style>
@endsection

@section('content')
    <div class="content-box">
        <div class="title">Internal Server Error<br>
            “We hit an unexpected error.”</div>

        <div class="message">
            It’s not you. It’s us. Try refreshing the page, or come back a little later.
        </div>

        <a href="{{ url('/') }}" class="btn-home">Go to homepage</a>
    </div>
@endsection
