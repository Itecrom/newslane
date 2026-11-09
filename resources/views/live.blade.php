@extends('layouts.app')

@section('title', 'Live Coverage | NEWSLANE')
@section('meta_description', 'Follow NEWSLANE live coverage and rolling updates from Malawi, Africa and the world.')

@section('content')
    <section class="shell listing-page">
        <div class="listing-heading"><p class="eyebrow">NEWSLANE / REAL-TIME REPORTING</p><h1><span class="live-pulse">LIVE</span> Coverage</h1><p>Rolling updates from the NEWSLANE newsroom.</p></div>
        @if ($events->isNotEmpty())
            <div class="live-event-grid">@foreach ($events as $event)<a class="live-event-card" href="{{ route('live.show', $event->slug) }}"><span class="live-pulse">LIVE NOW</span><h2>{{ $event->title }}</h2><p>{{ $event->summary }}</p><span class="text-link">Follow updates <span>→</span></span></a>@endforeach</div>
        @else
            <div class="empty-state"><span class="empty-mark">●</span><div><h2>Nothing live right now.</h2><p>When major events unfold, follow the story here with verified, timestamped updates.</p><a class="text-link" href="{{ route('home') }}">Browse latest news <span>→</span></a></div></div>
        @endif
    </section>
@endsection
