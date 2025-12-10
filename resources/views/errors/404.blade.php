@extends('errors.layout')

@section('title', '404 Not Found')

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
            <div class="title">Not Found <br>
                “Well… this is awkward.”</div>

            <div class="message">
                We can’t find the page you were looking for. It might have been removed, renamed, or never existed in the first place.
            </div>

            <a href="{{ url('/') }}" class="btn-home">Return To Dashboard</a>
        </div>
@endsection
