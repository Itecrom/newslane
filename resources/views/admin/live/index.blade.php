@extends('layouts.admin')

@section('title', 'Live coverage | NEWSLANE')
@section('admin_title', 'Live coverage')

@section('admin_content')
    <div class="admin-content-grid live-admin-layout">
        <section class="admin-panel"><div class="panel-heading"><div><p class="eyebrow">REAL-TIME REPORTING</p><h2>Coverage desk</h2></div><span class="muted-copy">{{ $events->count() }} events</span></div>
            @forelse ($events as $event)
                <article class="live-admin-event"><div class="panel-heading"><div><span class="status-badge status-{{ $event->status }}">{{ strtoupper($event->status) }}</span><h3>{{ $event->title }}</h3><p>{{ $event->summary }}</p></div><div class="event-actions"><a class="small-action" href="{{ route('live.show', $event->slug) }}" target="_blank" rel="noopener">View ↗</a>@if ($event->status === 'live')<form action="{{ route('admin.live.close', $event) }}" method="post">@csrf @method('PATCH')<button class="small-action small-danger" type="submit">Close coverage</button></form>@endif<form action="{{ route('admin.live.destroy', $event) }}" method="post" data-confirm="Delete this live event and its updates?">@csrf @method('DELETE')<button class="small-action small-danger" type="submit">Delete</button></form></div></div>
                    @if ($event->status === 'live')<form class="live-update-form" action="{{ route('admin.live.updates.store', $event) }}" method="post">@csrf<label>Add a verified update<textarea name="body" rows="3" required minlength="3" maxlength="5000" placeholder="Write a concise, timestamped newsroom update…"></textarea></label><button class="button button-blue" type="submit">Publish update <span>→</span></button></form>@endif
                    <small>{{ $event->updates_count }} updates · Started {{ $event->started_at?->format('d M Y, H:i') }} CAT</small>
                </article>
            @empty <p class="admin-quiet">No live coverage is running. Start an event when your newsroom is ready.</p> @endforelse
        </section>
        <section class="admin-panel category-create"><p class="eyebrow">START COVERAGE</p><h2>New live event</h2><form action="{{ route('admin.live.store') }}" method="post" class="stack-form">@csrf
            <label>Event headline<input name="title" required maxlength="200" placeholder="What are we covering?"></label>
            <label>Public URL slug <span class="field-help">Optional; generated from the headline.</span><input name="slug" maxlength="190"></label>
            <label>Brief summary<textarea name="summary" rows="4" required maxlength="500" placeholder="A short overview for readers"></textarea></label>
            <button class="button button-blue" type="submit">Start live coverage <span>→</span></button>
        </form><p class="form-note">Updates are published immediately with a timestamp and editor attribution. Verify information before publishing.</p></section>
    </div>
@endsection
