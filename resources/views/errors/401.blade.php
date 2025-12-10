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
        <div class="title">Not Authorized
            <br>
            “You don’t have access to this page.”</div>

        <div class="message">
            It looks like your account doesn’t have permission to view this content. If you think this is a mistake, please reach out to your administrator.
        </div>

        <a href="{{ url('/') }}" class="btn-home">Go back home</a>
    </div>
@endsection
