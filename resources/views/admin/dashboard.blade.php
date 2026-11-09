@extends('layouts.admin')

@section('title', 'Control centre | NEWSLANE')
@section('admin_title', 'Good day, '.auth()->user()->name)
@section('admin_action')<a class="button button-blue" href="{{ route('admin.articles.create') }}">＋ Write a story</a>@endsection

@section('admin_content')
    <p class="admin-welcome">Here’s what’s happening across your newsroom today.</p>
    <div class="stats-grid">
        <a class="stat-card stat-blue" href="{{ route('admin.articles.index') }}"><span>Published stories</span><strong>{{ number_format($stats['published']) }}</strong><small>On NEWSLANE</small><i>↗</i></a>
        <a class="stat-card" href="{{ route('admin.articles.index') }}"><span>Drafts</span><strong>{{ number_format($stats['drafts']) }}</strong><small>Ready for review</small><i>↗</i></a>
        <a class="stat-card stat-red" href="{{ route('admin.comments.index') }}"><span>Comments to review</span><strong>{{ number_format($stats['comments']) }}</strong><small>Awaiting moderation</small><i>↗</i></a>
        <a class="stat-card" href="{{ route('admin.live.index') }}"><span>Live coverage</span><strong>{{ number_format($stats['live']) }}</strong><small>Active now</small><i>↗</i></a>
    </div>
    <div class="admin-content-grid">
        <section class="admin-panel"><div class="panel-heading"><div><p class="eyebrow">EDITORIAL</p><h2>Recent stories</h2></div><a href="{{ route('admin.articles.index') }}">All stories →</a></div>
            @forelse ($articles as $article)
                <div class="admin-story-row"><span class="status-dot status-{{ $article->status }}"></span><div class="admin-story-main"><strong>{{ $article->title }}</strong><small>{{ $article->category->name }} · {{ $article->author->name }}</small></div><span class="status-badge status-{{ $article->status }}">{{ ucfirst($article->status) }}</span><a class="icon-link" href="{{ route('admin.articles.edit', $article) }}" aria-label="Edit {{ $article->title }}">↗</a></div>
            @empty
                <div class="admin-empty"><span class="empty-mark">N</span><div><h3>Your newsroom is ready.</h3><p>Create your first story to start building the front page.</p><a class="text-link" href="{{ route('admin.articles.create') }}">Write a story <span>→</span></a></div></div>
            @endforelse
        </section>
        <section class="admin-panel"><div class="panel-heading"><div><p class="eyebrow">COMMUNITY</p><h2>Comments to review</h2></div><a href="{{ route('admin.comments.index') }}">Moderate →</a></div>
            @forelse ($comments as $comment)
                <div class="pending-comment"><div class="comment-head"><strong>{{ $comment->name }}</strong><time>{{ $comment->created_at->diffForHumans() }}</time></div><p>{{ Str::limit($comment->body, 115) }}</p><small>On: {{ $comment->article->title }}</small><form action="{{ route('admin.comments.approve', $comment) }}" method="post">@csrf @method('PATCH')<button class="small-action" type="submit">Approve</button></form></div>
            @empty
                <p class="admin-quiet">No comments are waiting for review.</p>
            @endforelse
        </section>
    </div>
    <div class="admin-quick-links"><a href="{{ route('admin.categories.index') }}"><span>▦</span><strong>Manage categories</strong><small>Organize your newsroom</small><b>→</b></a><a href="{{ route('admin.ads.index') }}"><span>▧</span><strong>Manage advertising</strong><small>Placements and campaigns</small><b>→</b></a><a href="{{ route('admin.live.index') }}"><span>◉</span><strong>Go live</strong><small>Start rolling coverage</small><b>→</b></a>@if (auth()->user()->isAdmin())<a href="{{ route('admin.users.index') }}"><span>♙</span><strong>Staff access</strong><small>Manage newsroom accounts</small><b>→</b></a>@endif</div>
@endsection
