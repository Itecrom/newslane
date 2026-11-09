@extends('layouts.admin')

@section('title', ($article->exists ? 'Edit story' : 'Write a story').' | NEWSLANE')
@section('admin_title', $article->exists ? 'Edit story' : 'Write a story')
@section('admin_action')<a class="button button-light" href="{{ route('admin.articles.index') }}">← All stories</a>@endsection

@section('admin_content')
    <form class="editor-form admin-panel" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}" method="post" enctype="multipart/form-data">
        @csrf @if ($article->exists) @method('PUT') @endif
        <div class="editor-main">
            <label>Headline<input name="title" value="{{ old('title', $article->title) }}" required maxlength="255" placeholder="Write a clear, compelling headline"></label>
            <label>Story URL <span class="field-help">Letters, numbers and hyphens only. Leave blank to generate from headline.</span><div class="slug-field"><span>{{ url('/') }}/</span><input name="slug" value="{{ old('slug', $article->slug) }}" maxlength="190" placeholder="headline-slug"></div></label>
            <label>Summary <span class="field-help">Shown on article cards and in search results (max 320 characters).</span><textarea name="excerpt" rows="3" maxlength="320" required>{{ old('excerpt', $article->excerpt) }}</textarea></label>
            <label>Story body <span class="field-help">Plain text is safely escaped on publication. Separate paragraphs with a blank line.</span><textarea class="body-editor" name="body" rows="18" required minlength="20">{{ old('body', $article->body) }}</textarea></label>
            <label>SEO description <span class="field-help">Optional. Defaults to the story summary (max 160 characters).</span><textarea name="meta_description" rows="2" maxlength="160">{{ old('meta_description', $article->meta_description) }}</textarea></label>
        </div>
        <aside class="editor-settings">
            <h2>Story settings</h2>
            <label>Category<select name="category_id" required><option value="">Choose category</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $article->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label>Publication status<select name="status"><option value="draft" @selected(old('status', $article->status ?: 'draft') === 'draft')>Draft</option><option value="published" @selected(old('status', $article->status) === 'published')>Published</option></select></label>
            <label>Publish date <input type="datetime-local" name="published_at" value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}"></label>
            <label>Featured image <input type="file" name="cover" accept="image/jpeg,image/png,image/webp,image/avif"><span class="field-help">JPEG, PNG, WebP or AVIF. Max 5 MB.</span></label>
            @if ($article->cover_image)<img class="editor-preview" src="{{ asset('storage/'.$article->cover_image) }}" alt="{{ $article->image_alt ?: 'Current story cover' }}">@endif
            <label>Image description <input name="image_alt" value="{{ old('image_alt', $article->image_alt) }}" maxlength="180" placeholder="Describe the image for accessibility"></label>
            <label class="checkbox-label"><input type="checkbox" name="featured" value="1" @checked(old('featured', $article->featured))> Feature on the homepage</label>
            <label class="checkbox-label"><input type="checkbox" name="breaking" value="1" @checked(old('breaking', $article->breaking))> Mark as breaking news</label>
            <button class="button button-blue button-full" type="submit">{{ $article->exists ? 'Save changes' : 'Publish story' }} <span>→</span></button>
            @if ($article->exists && $article->status === 'published')<a class="button button-light button-full" href="{{ route('articles.show', $article->slug) }}" target="_blank" rel="noopener">Preview public story ↗</a>@endif
        </aside>
    </form>
@endsection
