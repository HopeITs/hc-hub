@extends('errors.layout')

@section('title', 'Forbidden')

@section('page-background')
    <style>
        body {
            background: url('{{ asset('assets/errors/403.png') }}') no-repeat center center;
            background-size: cover;
            position: relative;
            top:0;
        }
    </style>
@endsection

@section('content')
    <div class="content-box">
        <div class="title">@lang('general.403-title1')<br>
            @lang('general.403-title2')</div>

        <div class="message">
            @lang('general.403-message')
        </div>

        <a href="{{ url('/') }}" class="btn-home">@lang('general.403-button')</a>
    </div>
@endsection
