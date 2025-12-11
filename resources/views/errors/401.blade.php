@extends('errors.layout')

@section('title', '401 Unauthorized')

@section('content')
    <img src="{{ asset('assets/errors/401.png') }}" class="error-image">

    <div class="title">Unauthorized</div>

    <div class="message">
        You are not authorized to access this page.
    </div>

    <a href="{{ route('login') }}" class="btn-home">Login</a>
@endsection
