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
        <div class="title">@lang('general.500-title1')<br>
            @lang('general.500-title2')</div>

        <div class="message">
            @lang('general.500-message')
        </div>

        <a href="{{ url('/') }}" class="btn-home">@lang('general.500-button')</a>
    </div>
@endsection
