<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NEWSLANE | Your World. Your News.')</title>
    <meta name="description" content="@yield('meta_description', 'Independent journalism from Malawi, Africa and the world. Your World. Your News.')">
    <meta name="robots" content="{{ request()->routeIs('admin.*', 'search') ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' }}">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:site_name" content="NEWSLANE">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:locale" content="en_MW">
    <meta property="og:title" content="@yield('og_title', $__env->yieldContent('title', 'NEWSLANE | Your World. Your News.'))">
    <meta property="og:description" content="@yield('og_description', $__env->yieldContent('meta_description', 'Independent journalism. Malawi • Africa • World.'))">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:image" content="@yield('og_image', asset('images/newslane-brand-board.png'))">
    <meta property="og:image:alt" content="@yield('og_image_alt', 'NEWSLANE — Your World. Your News.')">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', $__env->yieldContent('title', 'NEWSLANE | Your World. Your News.'))">
    <meta name="twitter:description" content="@yield('og_description', $__env->yieldContent('meta_description', 'Independent journalism. Malawi • Africa • World.'))">
    <meta name="twitter:image" content="@yield('og_image', asset('images/newslane-brand-board.png'))">
    <meta name="twitter:image:alt" content="@yield('og_image_alt', 'NEWSLANE — Your World. Your News.')">
    <meta name="theme-color" content="#0B2D6B">
    @php($adsenseClientId = config('services.adsense.client_id'))
    @if (! request()->routeIs('admin.*') && $adsenseEnabled && is_string($adsenseClientId) && preg_match('/^ca-pub-[0-9]+$/D', $adsenseClientId))
        <meta name="google-adsense-account" content="{{ $adsenseClientId }}">
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $adsenseClientId }}" crossorigin="anonymous"></script>
    @endif
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/newslane-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    @stack('head')
</head>
<body>
    <div class="topbar">
        <div class="shell topbar-inner">
            <span>Independent journalism. African perspectives.</span>
            <div class="topbar-right">
                <a class="topbar-contact" href="{{ route('contact') }}">Contact NEWSLANE</a>
                <span class="topbar-divider topbar-contact-divider">|</span>
                <span class="topbar-coverage">Malawi <span class="dot">•</span> Africa <span class="dot">•</span> World</span>
                <span class="topbar-divider topbar-date-divider">|</span>
                <span class="topbar-date"><time id="local-clock">{{ now()->timezone('Africa/Blantyre')->format('D, d M Y · H:i') }}</time> CAT</span>
                @include('partials.weather-widget')
            </div>
        </div>
    </div>

    <header class="site-header">
        <div class="shell brand-row">
            <a class="brand" href="{{ route('home') }}" aria-label="NEWSLANE home">
                <img src="{{ asset('images/newslane-mark.svg') }}" width="48" height="48" alt="">
                <span class="brand-type"><span>NEWS</span><strong>LANE</strong><small>Your World. Your News.</small></span>
            </a>
            <div class="brand-coverage">MALAWI <b>•</b> AFRICA <b>•</b> WORLD</div>
            <form class="search-form" action="{{ route('search') }}" method="get" role="search">
                <label class="sr-only" for="site-search">Search stories</label>
                <input id="site-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search the news">
                <button aria-label="Search" type="submit">⌕</button>
            </form>
            <button class="theme-toggle" type="button" data-theme-toggle aria-label="Enable dark mode" aria-pressed="false"><span aria-hidden="true" data-theme-icon>◐</span><span class="theme-toggle-label">Dark mode</span></button>
        </div>
        <nav class="main-nav" aria-label="Main navigation">
            <div class="shell nav-inner">
                <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
                @foreach ($navCategories as $navCategory)
                    <a class="{{ request()->route('category')?->id === $navCategory->id ? 'active' : '' }}" href="{{ route('category.show', $navCategory->slug) }}">{{ $navCategory->name }}</a>
                @endforeach
                <a class="nav-live" href="{{ route('live.index') }}"><span></span> Live</a>
            </div>
        </nav>
    </header>

    @if (session('status'))
        <div class="shell flash-message" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="shell flash-message flash-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="shell footer-grid">
            <div>
                <a class="brand brand-footer" href="{{ route('home') }}">
                    <img src="{{ asset('images/newslane-mark.svg') }}" width="42" height="42" alt="">
                    <span class="brand-type"><span>NEWS</span><strong>LANE</strong><small>Your World. Your News.</small></span>
                </a>
                <p>Independent journalism. Fearless reporting.<br>Malawi <span class="dot">•</span> Africa <span class="dot">•</span> World</p>
            </div>
            <div class="footer-links">
                <strong>Explore</strong>
                <a href="{{ route('home') }}">Latest news</a>
                <a href="{{ route('live.index') }}">Live coverage</a>
                <a href="{{ route('contact') }}">Contact NEWSLANE</a>
                <a href="{{ route('sitemap') }}">Sitemap</a>
                <a href="https://facebook.com/newslanemw" target="_blank" rel="noopener noreferrer">Follow NEWSLANE on Facebook ↗</a>
            </div>
            <div class="footer-newsletter">
                <strong>The NEWSLANE briefing</strong>
                <p>Our essential stories, delivered to your inbox.</p>
                <form action="{{ route('newsletter.store') }}" method="post" class="newsletter-form">
                    @csrf
                    <label class="sr-only" for="footer-email">Email address</label>
                    <input id="footer-email" type="email" name="email" placeholder="Your email address" required maxlength="254">
                    <button type="submit">Subscribe</button>
                </form>
            </div>
        </div>
        <div class="shell footer-contacts"><a href="mailto:info@newslane.africa">info@newslane.africa</a><a href="mailto:sales@newslane.africa">sales@newslane.africa</a><a href="mailto:editor@newslane.africa">editor@newslane.africa</a></div>
        <div class="shell footer-bottom"><span>© {{ now()->year }} NEWSLANE. All rights reserved.</span><a href="{{ route('admin.login') }}">Editorial access</a></div>
    </footer>
    <script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
