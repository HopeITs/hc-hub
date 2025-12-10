@extends('errors.layout')

@section('title', 'Forbidden')

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
        <div class="title">Forbidden<br>
            “You don’t have permission to view this page.”</div>

        <div class="message">
            You might not have the required permissions to open this page. If something feels off, contact your administrator for help.
        </div>

        <a href="{{ url('/') }}" class="btn-home">Go to Safety</a>
    </div>
@endsection
