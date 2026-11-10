<article class="story-card">
    <a class="story-image" href="{{ route('articles.show', $story->slug) }}">
        @if ($story->cover_image)
            <img src="{{ asset('storage/'.$story->cover_image) }}" alt="{{ $story->image_alt ?: $story->title }}" loading="lazy">
        @else
            <span class="image-placeholder" aria-hidden="true"><span>{{ strtoupper(substr($story->category->name ?? 'N', 0, 1)) }}</span></span>
        @endif
        <span class="image-label">{{ $story->category->name ?? 'News' }}</span>
    </a>
    <div class="story-card-body">
        <div class="story-meta"><span>{{ $story->category->name ?? 'News' }}</span><time datetime="{{ $story->published_at?->toIso8601String() }}">{{ $story->published_at?->diffForHumans() }}</time></div>
        <h3><a href="{{ route('articles.show', $story->slug) }}">{{ $story->title }}</a></h3>
        <p>{{ $story->excerpt }}</p>
        <div class="story-byline">By {{ $story->author->name ?? 'NEWSLANE Desk' }}</div>
        @if ($story->image_credit)
            <div class="story-image-credit">Photo taken by @if ($story->image_credit_url)<a href="{{ $story->image_credit_url }}" target="_blank" rel="noopener noreferrer">{{ $story->image_credit }}</a>@else{{ $story->image_credit }}@endif</div>
        @endif
    </div>
</article>
