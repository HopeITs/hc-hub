@extends('errors.layout')

@section('title', 'Maintenance Mode')

@section('page-background')
    <style>
        body {
            background: url('{{ asset('assets/errors/503.jpg') }}') no-repeat center center;
            background-size: cover;
            position: relative;
            top:0;
        }
    </style>
@endsection

@section('content')
    <div class="content-box">
        <div class="title">@lang('general.503-title1')<br>
            @lang('general.503-title2')</div>

        <div class="message">
            @lang('general.503-message')
        </div>

        <a href="{{ url('/') }}" class="btn-home">@lang('general.503-button')</a>
    </div>
@endsection
