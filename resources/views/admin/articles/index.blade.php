@extends('layouts.admin')

@section('title', 'Stories | NEWSLANE')
@section('admin_title', 'Stories')
@section('admin_action')<a class="button button-blue" href="{{ route('admin.articles.create') }}">＋ {{ auth()->user()->canPublishArticles() ? 'Write a story' : 'Submit a draft' }}</a>@endsection

@section('admin_content')
    <section class="admin-panel table-panel"><div class="panel-heading"><div><p class="eyebrow">EDITORIAL DESK</p><h2>All stories</h2></div><span class="muted-copy">{{ $articles->total() }} total</span></div>
        <div class="table-scroll"><table><thead><tr><th>Story</th><th>Category</th><th>Author</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>
        @forelse ($articles as $article)
            <tr><td class="table-story"><strong>{{ $article->title }}</strong><small>/{{ $article->slug }}</small></td><td>{{ $article->category->name }}</td><td>{{ $article->author->name }}</td><td><span class="status-badge status-{{ $article->status }}">{{ ucfirst($article->status) }}</span></td><td>{{ $article->updated_at->format('d M Y') }}</td><td class="table-actions"><a href="{{ route('admin.articles.edit', $article) }}">Edit</a>@if ($article->status === 'published' && $article->published_at && ! $article->published_at->isFuture())
                @if ($article->facebook_post_id)
                    <span class="facebook-posted-label">Posted</span><a href="https://www.facebook.com/{{ rawurlencode($article->facebook_post_id) }}" target="_blank" rel="noopener noreferrer">Facebook ↗</a>
                @elseif (in_array($article->facebook_post_status, ['pending', 'publishing'], true))
                    <span class="facebook-queue-label">{{ $article->facebook_post_status === 'pending' ? 'Queued' : 'Posting' }}</span>
                @elseif (auth()->user()->canPublishArticles())
                    <form action="{{ route('admin.articles.facebook', $article) }}" method="post">@csrf<button class="facebook-share-button" type="submit">Post to Facebook</button></form>
                @endif
                @if ($article->facebook_post_error)<small class="facebook-post-error">{{ $article->facebook_post_error }}</small>@endif
            @endif @if (auth()->user()->canManageNewsroom())<form action="{{ route('admin.articles.destroy', $article) }}" method="post" data-confirm="Delete this story permanently?">@csrf @method('DELETE')<button type="submit">Delete</button></form>@endif @if ($article->status === 'published')<a href="{{ route('articles.show', $article->slug) }}" target="_blank" rel="noopener">View</a>@endif</td></tr>
        @empty
            <tr><td colspan="6"><div class="table-empty"><h3>No stories yet</h3><p>Start your first story in the editor.</p><a class="button button-blue" href="{{ route('admin.articles.create') }}">Write a story</a></div></td></tr>
        @endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $articles->links() }}</div>
    </section>
@endsection
