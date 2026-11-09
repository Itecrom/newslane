@extends('layouts.app')

@section('title', $event->title.' — Live Coverage | NEWSLANE')
@section('meta_description', $event->summary)

@section('content')
    <section class="shell live-detail">
        <nav class="breadcrumbs"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('live.index') }}">Live coverage</a><span>/</span><span>{{ $event->title }}</span></nav>
        <header class="live-detail-header"><span class="live-pulse">LIVE COVERAGE</span><h1>{{ $event->title }}</h1><p>{{ $event->summary }}</p><time>Started {{ $event->started_at?->format('d F Y · H:i') }} CAT</time><small class="live-refresh-status" aria-live="polite">Updates refresh automatically.</small></header>
        <div class="live-timeline" data-live-updates-url="{{ route('live.updates', $event->slug) }}" data-last-update-id="{{ $event->updates->last()?->id ?? 0 }}">@forelse ($event->updates as $update)<article class="live-update" data-update-id="{{ $update->id }}"><time datetime="{{ $update->created_at->toIso8601String() }}">{{ $update->created_at->format('H:i') }} CAT <span>{{ $update->created_at->format('d M Y') }}</span></time><div><span class="timeline-dot"></span><p>{!! nl2br(e($update->body)) !!}<small>NEWSLANE · {{ $update->author->name }}</small></p></div></article>@empty<p class="muted-copy">Updates will appear here as our newsroom verifies new information.</p>@endforelse</div>
    </section>
@endsection
