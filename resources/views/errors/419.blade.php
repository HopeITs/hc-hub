@extends('errors.layout')

@section('title', 'Page Expired')

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
        <div class="title">@lang('general.419-title1') <br>
            @lang('general.419-title2')</div>

        <div class="message">
            @lang('general.419-message')
        </div>

        <a href="{{ url('/') }}" class="btn-home">@lang('general.419-button')</a>
    </div>
@endsection
