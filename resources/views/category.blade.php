@extends('layouts.app')

@section('title', $category->name.' News | NEWSLANE')
@section('meta_description', $category->description ?: 'The latest '.$category->name.' news, analysis and reporting from NEWSLANE.')

@section('content')
    <section class="shell listing-page">
        <div class="listing-heading"><p class="eyebrow">NEWSLANE / COVERAGE</p><h1>{{ $category->name }}</h1><p>{{ $category->description ?: 'The latest reporting, analysis and perspectives from '.$category->name.'.' }}</p></div>
        @if ($articles->isNotEmpty())
            <div class="story-grid story-grid-list">@foreach ($articles as $story) @include('partials.story-card', ['story' => $story]) @endforeach</div>
            <div class="pagination-wrap">{{ $articles->links() }}</div>
        @else
            <div class="empty-state"><span class="empty-mark">N</span><div><h2>More from {{ $category->name }} soon.</h2><p>Our editors are preparing new stories. Explore the latest news in the meantime.</p><a class="text-link" href="{{ route('home') }}">Back to the front page <span>→</span></a></div></div>
        @endif
    </section>
@endsection
