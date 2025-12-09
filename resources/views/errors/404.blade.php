@extends('errors.layout')

@section('title', '404 Not Found')

@section('banner')
    <img src="{{ asset('assets/errors/404.png') }}" class="full-banner">
@endsection

@section('content')
    <div class="title">Page Not Found</div>

    <div class="message">
        The page you're looking for doesn't exist or may have been moved.
    </div>

    <a href="{{ url('/') }}" class="btn-home">Go Home</a>
@endsection
