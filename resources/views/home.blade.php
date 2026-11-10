@extends('layouts.app')

@section('title', 'NEWSLANE | Your World. Your News.')
@section('meta_description', 'Independent and credible journalism from Malawi, Africa and the world. Get the latest news, analysis and live coverage from NEWSLANE.')

@section('content')
    @if ($breaking->isNotEmpty())
        <section class="breaking-bar" aria-label="Breaking news">
            <div class="shell breaking-inner">
                <span class="breaking-label"><i></i> Breaking</span>
                <div class="ticker"><div class="ticker-track">
                    @foreach ($breaking as $story)
                        <a href="{{ route('articles.show', $story->slug) }}">{{ $story->title }}</a><span>◆</span>
                    @endforeach
                </div></div>
            </div>
        </section>
    @endif

    <section class="shell home-intro">
        <div><p class="eyebrow">{{ strtoupper(now()->timezone('Africa/Blantyre')->format('l, d F Y')) }}</p><h1>The stories that <span>move us.</span></h1></div>
        <p>Independent journalism. Fearless reporting.<br>From Malawi to the world.</p>
    </section>

    @if ($liveEvents->isNotEmpty())
        <section class="shell live-strip">
            <span class="live-pulse">LIVE NOW</span>
            @foreach ($liveEvents as $event)
                <a href="{{ route('live.show', $event->slug) }}">{{ $event->title }} <span aria-hidden="true">→</span></a>
            @endforeach
            <a class="live-all" href="{{ route('live.index') }}">All live coverage</a>
        </section>
    @endif

    <section class="shell lead-grid">
        @if ($featuredStories->isNotEmpty())
            <div class="lead-carousel" data-carousel data-carousel-interval="7000" aria-label="Featured stories">
                @foreach ($featuredStories as $slide => $featured)
                    <article class="lead-story feature-slide {{ $slide === 0 ? 'is-active' : '' }}" data-carousel-slide @if ($slide !== 0) hidden @endif>
                        <div class="lead-image">
                            <a class="lead-image-story" href="{{ route('articles.show', $featured->slug) }}" aria-label="{{ $featured->title }}">
                                @if ($featured->cover_image)
                                    <img src="{{ asset('storage/'.$featured->cover_image) }}" alt="{{ $featured->image_alt ?: $featured->title }}" @if ($slide > 0) loading="lazy" @endif>
                                @else
                                    <span class="lead-placeholder"><b>N</b><i></i><small>MALAWI · AFRICA · WORLD</small></span>
                                @endif
                                <span class="lead-category">{{ $featured->category->name }}</span>
                            </a>
                            @if ($featured->image_credit)
                                <p class="lead-image-caption">
                                    <span>Photo taken by @if ($featured->image_credit_url)<a href="{{ $featured->image_credit_url }}" target="_blank" rel="noopener noreferrer">{{ $featured->image_credit }}</a>@else{{ $featured->image_credit }}@endif</span>
                                </p>
                            @endif
                        </div>
                        <div class="lead-copy">
                            <div class="story-meta"><span>{{ $featured->category->name }}</span><time datetime="{{ $featured->published_at->toIso8601String() }}">{{ $featured->published_at->format('d M Y') }}@if ($featured->published_at->format('H:i:s') !== '00:00:00') · {{ $featured->published_at->format('H:i') }} CAT @endif</time></div>
                            <h2><a href="{{ route('articles.show', $featured->slug) }}">{{ $featured->title }}</a></h2>
                            <p>{{ $featured->excerpt }}</p>
                            <a class="text-link" href="{{ route('articles.show', $featured->slug) }}">Read the full story <span>→</span></a>
                        </div>
                    </article>
                @endforeach
                @if ($featuredStories->count() > 1)
                    <div class="carousel-controls" aria-label="Featured story controls">
                        <button type="button" data-carousel-prev aria-label="Previous featured story">←</button>
                        @foreach ($featuredStories as $slide => $featured)
                            <button type="button" data-carousel-dot="{{ $slide }}" aria-label="Show featured story {{ $slide + 1 }}" aria-pressed="{{ $slide === 0 ? 'true' : 'false' }}"></button>
                        @endforeach
                        <button type="button" data-carousel-next aria-label="Next featured story">→</button>
                    </div>
                @endif
            </div>
        @else
            <div>
            <article class="lead-story lead-empty">
                <div class="lead-placeholder"><b>N</b><i></i><small>MALAWI · AFRICA · WORLD</small></div>
                <div class="lead-copy"><div class="story-meta"><span>OUR PROMISE</span><span>Independent. Credible. Fearless.</span></div><h2>Your world.<br><em>Your news.</em></h2><p>Public-interest journalism with African perspectives and global reach. Our newsroom is getting ready to bring you the stories that matter.</p><a class="text-link" href="{{ route('admin.login') }}">Enter the newsroom <span>→</span></a></div>
            </article>
            </div>
        @endif

        <aside class="lead-rail">
            <div class="section-heading"><h2>Most read</h2><span class="heading-rule"></span></div>
            @forelse ($popular as $index => $story)
                <article class="rail-story">
                    <span class="rail-number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <a class="rail-thumb" href="{{ route('articles.show', $story->slug) }}" aria-label="{{ $story->title }}">@if ($story->cover_image)<img src="{{ asset('storage/'.$story->cover_image) }}" alt="{{ $story->image_alt ?: $story->title }}" loading="lazy">@else<span>{{ strtoupper(substr($story->category->name ?? 'N', 0, 1)) }}</span>@endif</a>
                    <div><span class="rail-category">{{ $story->category->name }}</span><h3><a href="{{ route('articles.show', $story->slug) }}">{{ $story->title }}</a></h3><small>{{ number_format($story->views) }} reads</small></div>
                </article>
            @empty
                <div class="rail-empty"><span class="rail-number">01</span><span class="rail-thumb"><span>N</span></span><p>Our newsroom is preparing the first edition. Check back soon.</p></div>
            @endforelse
            <div class="rail-values"><span>INDEPENDENT</span><i></i><span>IN-DEPTH</span><i></i><span>PUBLIC INTEREST</span></div>
        </aside>
    </section>

    @foreach ($advertisements as $advertisement)
        <div class="shell ad-wrap"><a href="{{ $advertisement->target_url }}" target="_blank" rel="sponsored noopener noreferrer"><img src="{{ asset('storage/'.$advertisement->image_path) }}" alt="{{ $advertisement->title }}" loading="lazy"></a><span>Advertisement</span></div>
    @endforeach

    <section class="shell latest-section">
        <div class="section-heading"><div><p class="eyebrow">STAY INFORMED</p><h2>Latest stories</h2></div><span class="heading-rule"></span><a href="{{ route('search') }}" class="text-link">Explore all <span>→</span></a></div>
        @if ($latest->isNotEmpty())
            <div class="story-grid">@foreach ($latest as $story) @include('partials.story-card', ['story' => $story]) @endforeach</div>
        @else
            <div class="empty-state"><span class="empty-mark">N</span><div><h3>Good journalism takes a little time.</h3><p>We’re preparing our first stories. Subscribe below and be the first to know when we publish.</p></div></div>
        @endif
    </section>

    @foreach ($homeSections as $section)
        <section class="shell category-home-section">
            <div class="section-heading"><div><p class="eyebrow">MORE FROM OUR NEWSROOM</p><h2>{{ $section->name }}</h2></div><span class="heading-rule"></span><a href="{{ route('category.show', $section->slug) }}" class="text-link">All {{ $section->name }} <span>→</span></a></div>
            <div class="story-grid">@foreach ($section->articles as $story) @include('partials.story-card', ['story' => $story]) @endforeach</div>
        </section>
    @endforeach

    @if ($videoEvents->isNotEmpty())
        <section class="video-news-band"><div class="shell video-news-section">
            <div class="section-heading"><div><p class="eyebrow">FROM THE SCENE</p><h2>Live video</h2></div><span class="heading-rule"></span><a href="{{ route('live.index') }}" class="text-link">All live coverage <span>→</span></a></div>
            <div class="live-video-grid">
                @foreach ($videoEvents as $event)
                    <article class="live-video-card"><div class="live-video-frame"><iframe src="{{ $event->video_embed_url }}" title="{{ $event->title }} — NEWSLANE live video" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div><div class="live-video-copy"><span class="live-pulse">LIVE NOW</span><h3><a href="{{ route('live.show', $event->slug) }}">{{ $event->title }}</a></h3><p>{{ $event->summary }}</p></div></article>
                @endforeach
            </div>
        </div></section>
    @endif

    <section class="newsletter-band"><div class="shell newsletter-inner"><div><p class="eyebrow">THE NEWSLANE BRIEFING</p><h2>The world, in your inbox.</h2><p>Our essential stories, delivered with care. No noise. Unsubscribe anytime.</p></div><form action="{{ route('newsletter.store') }}" method="post" class="newsletter-form">@csrf<label class="sr-only" for="home-email">Email address</label><input id="home-email" type="email" name="email" placeholder="Enter your email address" required maxlength="254"><button type="submit">Subscribe <span>→</span></button><small>By subscribing, you agree to receive the NEWSLANE newsletter.</small></form></div></section>
@endsection
