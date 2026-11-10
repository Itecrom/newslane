@extends('layouts.app')

@section('content')
    <div class="shell admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-user"><span class="author-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->role) }} account</small></div></div>
            <nav aria-label="Editorial dashboard">
                <a class="{{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" href="{{ route('admin.profile.edit') }}"><span>♙</span> My profile</a>
                @if (auth()->user()->canManageNewsroom())<a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span>◫</span> Overview</a>@endif
                <a class="{{ request()->routeIs('admin.articles.*') ? 'active' : '' }}" href="{{ route('admin.articles.index') }}"><span>▤</span> Stories</a>
                @if (auth()->user()->canManageNewsroom())
                    <a class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}"><span>▦</span> Categories</a>
                    <a class="{{ request()->routeIs('admin.comments.*') ? 'active' : '' }}" href="{{ route('admin.comments.index') }}"><span>◌</span> Comments</a>
                    <a class="{{ request()->routeIs('admin.ads.*') ? 'active' : '' }}" href="{{ route('admin.ads.index') }}"><span>▧</span> Advertising</a>
                @endif
                @if (auth()->user()->isAdmin())<a class="{{ request()->routeIs('admin.adsense.*') ? 'active' : '' }}" href="{{ route('admin.adsense.edit') }}"><span>G</span> Google AdSense</a>@endif
                @if (auth()->user()->canManageNewsroom())<a class="{{ request()->routeIs('admin.live.*') ? 'active' : '' }}" href="{{ route('admin.live.index') }}"><span>◉</span> Live coverage</a>@endif
                @if (auth()->user()->isAdmin())<a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span>♙</span> Staff accounts</a>@endif
                @if (auth()->user()->isAdmin())<a class="{{ request()->routeIs('admin.inquiries.*') ? 'active' : '' }}" href="{{ route('admin.inquiries.index') }}"><span>✉</span> Inquiries</a>@endif
                <a href="{{ route('home') }}" target="_blank" rel="noopener"><span>↗</span> View website</a>
            </nav>
            <form action="{{ route('admin.logout') }}" method="post">@csrf<button class="sidebar-logout" type="submit">Sign out <span>↗</span></button></form>
        </aside>
        <div class="admin-main">
            <div class="admin-page-heading"><div><p class="eyebrow">NEWSLANE / CONTROL CENTRE</p><h1>@yield('admin_title', 'Overview')</h1></div>@yield('admin_action')</div>
            @yield('admin_content')
        </div>
    </div>
@endsection
