@extends('layouts.app')

@section('title', 'Newsletter | NEWSLANE')

@section('content')
    <section class="shell result-page"><span class="result-check">✓</span><p class="eyebrow">THE NEWSLANE BRIEFING</p><h1>{{ $message }}</h1><a class="button button-blue" href="{{ route('home') }}">Back to NEWSLANE</a></section>
@endsection
