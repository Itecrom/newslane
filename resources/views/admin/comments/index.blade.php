@extends('layouts.admin')

@section('title', 'Comments | NEWSLANE')
@section('admin_title', 'Comment moderation')

@section('admin_content')
    <section class="admin-panel table-panel"><div class="panel-heading"><div><p class="eyebrow">COMMUNITY STANDARDS</p><h2>Reader comments</h2></div><span class="muted-copy">{{ $comments->total() }} total</span></div>
        <div class="comment-moderation-list">
            @forelse ($comments as $comment)
                <article class="moderation-item"><div class="comment-head"><strong>{{ $comment->name }}</strong><time>{{ $comment->created_at->format('d M Y · H:i') }}</time><span class="status-badge status-{{ $comment->status }}">{{ ucfirst($comment->status) }}</span></div><p>{{ $comment->body }}</p><small>On <a href="{{ route('articles.show', $comment->article->slug) }}" target="_blank" rel="noopener">{{ $comment->article->title }}</a> · {{ $comment->email }}</small>
                    <div class="moderation-actions">@if ($comment->status !== 'approved')<form action="{{ route('admin.comments.approve', $comment) }}" method="post">@csrf @method('PATCH')<button class="small-action" type="submit">Approve</button></form>@endif<form action="{{ route('admin.comments.destroy', $comment) }}" method="post" data-confirm="Delete this comment?">@csrf @method('DELETE')<button class="small-action small-danger" type="submit">Delete</button></form></div>
                </article>
            @empty <div class="table-empty"><h3>No comments yet</h3><p>Reader comments will appear here for review.</p></div> @endforelse
        </div><div class="pagination-wrap">{{ $comments->links() }}</div>
    </section>
@endsection
