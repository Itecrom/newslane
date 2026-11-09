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
        @if ($featured)
            <article class="lead-story">
                <a class="lead-image" href="{{ route('articles.show', $featured->slug) }}" aria-label="{{ $featured->title }}">
                    @if ($featured->cover_image)
                        <img src="{{ asset('storage/'.$featured->cover_image) }}" alt="{{ $featured->image_alt ?: $featured->title }}">
                    @else
                        <span class="lead-placeholder"><b>N</b><i></i><small>MALAWI · AFRICA · WORLD</small></span>
                    @endif
                    <span class="lead-category">{{ $featured->category->name }}</span>
                </a>
                <div class="lead-copy">
                    <div class="story-meta"><span>{{ $featured->category->name }}</span><time datetime="{{ $featured->published_at->toIso8601String() }}">{{ $featured->published_at->format('d M Y · H:i') }} CAT</time></div>
                    <h2><a href="{{ route('articles.show', $featured->slug) }}">{{ $featured->title }}</a></h2>
                    <p>{{ $featured->excerpt }}</p>
                    <a class="text-link" href="{{ route('articles.show', $featured->slug) }}">Read the full story <span>→</span></a>
                </div>
            </article>
        @else
            <article class="lead-story lead-empty">
                <div class="lead-placeholder"><b>N</b><i></i><small>MALAWI · AFRICA · WORLD</small></div>
                <div class="lead-copy"><div class="story-meta"><span>OUR PROMISE</span><span>Independent. Credible. Fearless.</span></div><h2>Your world.<br><em>Your news.</em></h2><p>Public-interest journalism with African perspectives and global reach. Our newsroom is getting ready to bring you the stories that matter.</p><a class="text-link" href="{{ route('admin.login') }}">Enter the newsroom <span>→</span></a></div>
            </article>
        @endif

        <aside class="lead-rail">
            <div class="section-heading"><h2>Most read</h2><span class="heading-rule"></span></div>
            @forelse ($popular as $index => $story)
                <article class="rail-story">
                    <span class="rail-number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <div><span class="rail-category">{{ $story->category->name }}</span><h3><a href="{{ route('articles.show', $story->slug) }}">{{ $story->title }}</a></h3><small>{{ number_format($story->views) }} reads</small></div>
                </article>
            @empty
                <div class="rail-empty"><span class="rail-number">01</span><p>Our newsroom is preparing the first edition. Check back soon.</p></div>
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

    <section class="newsletter-band"><div class="shell newsletter-inner"><div><p class="eyebrow">THE NEWSLANE BRIEFING</p><h2>The world, in your inbox.</h2><p>Our essential stories, delivered with care. No noise. Unsubscribe anytime.</p></div><form action="{{ route('newsletter.store') }}" method="post" class="newsletter-form">@csrf<label class="sr-only" for="home-email">Email address</label><input id="home-email" type="email" name="email" placeholder="Enter your email address" required maxlength="254"><button type="submit">Subscribe <span>→</span></button><small>By subscribing, you agree to receive the NEWSLANE newsletter.</small></form></div></section>
@endsection
