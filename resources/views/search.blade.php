@extends('layouts.app')

@section('title', ($query ? 'Search: '.$query : 'Search news').' | NEWSLANE')
@section('meta_description', 'Search NEWSLANE reporting from Malawi, Africa and the world.')

@section('content')
    <section class="shell listing-page">
        <div class="listing-heading"><p class="eyebrow">NEWSLANE / SEARCH</p><h1>Find a story</h1><form action="{{ route('search') }}" method="get" class="search-page-form"><label class="sr-only" for="search-query">Search news</label><input id="search-query" type="search" name="q" value="{{ $query }}" placeholder="Search headlines, topics or categories"><button type="submit">Search</button></form>
        @if ($query)<p>Results for <strong>“{{ $query }}”</strong> <span class="muted-copy">· {{ $articles->total() }} {{ Str::plural('story', $articles->total()) }}</span></p>@endif</div>
        @if ($articles->isNotEmpty())
            <div class="story-grid story-grid-list">@foreach ($articles as $story) @include('partials.story-card', ['story' => $story]) @endforeach</div>
            <div class="pagination-wrap">{{ $articles->links() }}</div>
        @elseif ($query)
            <div class="empty-state"><span class="empty-mark">?</span><div><h2>No stories found</h2><p>Try a different search term or browse our coverage categories.</p></div></div>
        @else
            <div class="empty-state"><span class="empty-mark">N</span><div><h2>What would you like to know?</h2><p>Search headlines, topics or categories across NEWSLANE.</p></div></div>
        @endif
    </section>
@endsection
