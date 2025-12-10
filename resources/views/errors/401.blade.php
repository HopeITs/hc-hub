@extends('errors.layout')

@section('title', '401 Unauthorized')

@section('page-background')
    <style>
        body {
            background: url('{{ asset('assets/errors/401.png') }}') no-repeat center center;
            background-size: cover;
            position: relative;
            top:0;
        }
    </style>
@endsection

@section('content')
    <div class="content-box">
        <div class="title">@lang('general.401-title1')<br>
            @lang('general.401-title2')</div>

        <div class="message">
            @lang('general.401-message')
        </div>

        <a href="{{ url('/') }}" class="btn-home">@lang('general.401-button')</a>
    </div>
@endsection
