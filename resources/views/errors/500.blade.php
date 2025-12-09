@extends('errors.layout')

@section('title', '500 Server Error')

@section('content')
    <img src="{{ asset('assets/errors/500.png') }}" class="error-image">

    <div class="title">Server Error</div>

    <div class="message">
        Something went wrong on our side. Please try again later.
    </div>

    <a href="{{ url('/') }}" class="btn-home">Go Home</a>
@endsection
