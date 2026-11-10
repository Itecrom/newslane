@extends('layouts.app')

@section('title', $article->title.' | NEWSLANE')
@section('meta_description', $article->meta_description ?: $article->excerpt)
@section('og_type', 'article')
@section('og_title', $article->title)
@section('og_description', $article->meta_description ?: $article->excerpt)
@section('og_image', $article->cover_image ? asset('storage/'.$article->cover_image) : asset('images/newslane-brand-board.png'))
@section('og_image_alt', $article->image_alt ?: $article->title)
@section('og_url', route('articles.show', $article->slug))
@section('canonical', route('articles.show', $article->slug))

@push('head')
    <meta property="article:published_time" content="{{ $article->published_at->toIso8601String() }}">
    <meta property="article:section" content="{{ $article->category->name }}">
    <script type="application/ld+json" nonce="{{ $cspNonce }}">{{ \Illuminate\Support\Js::from(['@context' => 'https://schema.org', '@type' => 'NewsArticle', 'headline' => $article->title, 'description' => $article->meta_description ?: $article->excerpt, 'datePublished' => $article->published_at->toIso8601String(), 'dateModified' => $article->updated_at->toIso8601String(), 'author' => ['@type' => 'Person', 'name' => $article->author->name], 'image' => $article->cover_image ? asset('storage/'.$article->cover_image) : asset('images/newslane-brand-board.png'), 'mainEntityOfPage' => route('articles.show', $article->slug)]) }}</script>
@endpush

@section('content')
    <div class="shell article-page">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('category.show', $article->category->slug) }}">{{ $article->category->name }}</a><span>/</span><span>{{ $article->title }}</span></nav>
        <article class="article-layout">
            <div class="article-main">
                <header class="article-header">
                    <a class="category-kicker" href="{{ route('category.show', $article->category->slug) }}">{{ $article->category->name }}</a>
                    @if ($article->breaking)<span class="breaking-pill">BREAKING</span>@endif
                    <h1>{{ $article->title }}</h1>
                    <p class="article-deck">{{ $article->excerpt }}</p>
                    <div class="article-byline"><span class="author-avatar">{{ strtoupper(substr($article->author->name, 0, 1)) }}</span><div><strong>{{ $article->author->name }}</strong><time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->format('l, d F Y') }}@if ($article->published_at->format('H:i:s') !== '00:00:00') · {{ $article->published_at->format('H:i') }} CAT @endif</time></div><span class="article-views">{{ number_format($article->views) }} reads</span></div>
                </header>
                @if ($article->cover_image)
                    <figure class="article-cover">
                        <img src="{{ asset('storage/'.$article->cover_image) }}" alt="{{ $article->image_alt ?: $article->title }}">
                        @if ($article->image_credit)
                            <figcaption>
                                <span>Photo taken by @if ($article->image_credit_url)<a href="{{ $article->image_credit_url }}" target="_blank" rel="noopener noreferrer">{{ $article->image_credit }}</a>@else{{ $article->image_credit }}@endif</span>
                            </figcaption>
                        @endif
                    </figure>
                @endif
                @if ($article->video_embed_url)
                    <div class="live-detail-video"><iframe src="{{ $article->video_embed_url }}" title="{{ $article->title }} video" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>
                @endif
                <div class="article-body">{!! nl2br(e($article->body)) !!}</div>
                @if ($article->source_name)
                    <p class="article-source"><strong>Reporting credit:</strong> {{ $article->source_name }}@if ($article->source_url) · <a href="{{ $article->source_url }}" target="_blank" rel="noopener noreferrer">Original source ↗</a>@endif</p>
                @endif
                <div class="article-actions">
                    <form action="{{ route('articles.like', $article->slug) }}" method="post">@csrf<button class="like-button {{ $liked ? 'is-liked' : '' }}" type="submit" aria-pressed="{{ $liked ? 'true' : 'false' }}"><span aria-hidden="true">♥</span> {{ $liked ? 'Liked' : 'Like this story' }} <small>{{ number_format($article->likes_count) }}</small></button></form>
                    <div class="share-actions"><span>Share this story</span><button type="button" class="share-native" data-share-title="{{ $article->title }}" data-share-url="{{ route('articles.show', $article->slug) }}">Share</button><a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('articles.show', $article->slug)) }}" target="_blank" rel="noopener noreferrer">Facebook</a><a href="https://twitter.com/intent/tweet?url={{ urlencode(route('articles.show', $article->slug)) }}&text={{ urlencode($article->title) }}" target="_blank" rel="noopener noreferrer">X</a></div>
                </div>
                <section class="comments-section" id="comments">
                    <div class="section-heading"><div><p class="eyebrow">COMMUNITY</p><h2>Join the conversation <span>({{ $article->comments_count }})</span></h2></div><span class="heading-rule"></span></div>
                    <form action="{{ route('articles.comments.store', $article->slug) }}" method="post" class="comment-form">@csrf
                        <div class="form-pair"><label>Your name<input name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name"></label><label>Email address<input name="email" type="email" value="{{ old('email') }}" required maxlength="254" autocomplete="email"></label></div>
                        <label>Your comment<textarea name="body" rows="4" required minlength="3" maxlength="2000">{{ old('body') }}</textarea></label>
                        <p class="form-note">Comments are reviewed before appearing. Please keep the conversation respectful.</p>
                        <button class="button button-blue" type="submit">Submit comment</button>
                    </form>
                    <div class="comment-list">
                        @forelse ($article->comments as $comment)
                            <article class="comment"><span class="comment-avatar">{{ strtoupper(substr($comment->name, 0, 1)) }}</span><div><div class="comment-head"><strong>{{ $comment->name }}</strong><time>{{ $comment->created_at->diffForHumans() }}</time></div><p>{!! nl2br(e($comment->body)) !!}</p></div></article>
                        @empty
                            <p class="muted-copy">No comments yet. Be the first to share a thoughtful response.</p>
                        @endforelse
                    </div>
                </section>
            </div>
            <aside class="article-sidebar">
                <div class="sidebar-card"><p class="eyebrow">FROM THE NEWSROOM</p><h3>Independent journalism. Public-interest stories.</h3><p>NEWSLANE brings Malawi, Africa and the world into focus.</p><a class="text-link" href="{{ route('home') }}">More from NEWSLANE <span>→</span></a></div>
                @if ($advertisement)
                    <a class="article-ad" href="{{ $advertisement->target_url }}" target="_blank" rel="sponsored noopener noreferrer"><span>Advertisement</span><img src="{{ asset('storage/'.$advertisement->image_path) }}" alt="{{ $advertisement->title }}" loading="lazy"></a>
                @endif
                @if ($related->isNotEmpty())
                    <div class="related-stories"><div class="section-heading"><h2>More in {{ $article->category->name }}</h2></div>@foreach ($related as $story)<article><span>{{ $story->published_at->format('d M') }}</span><h3><a href="{{ route('articles.show', $story->slug) }}">{{ $story->title }}</a></h3></article>@endforeach</div>
                @endif
            </aside>
        </article>
    </div>
@endsection
