<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PublishArticleToFacebook;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class ArticleAdminController extends Controller
{
    public function index(): View
    {
        $articles = Article::with(['category', 'author'])
            ->when(! request()->user()->canManageNewsroom(), fn ($query) => $query->where('user_id', request()->user()->id))
            ->latest('updated_at')
            ->paginate(15);

        return view('admin.articles.index', compact('articles'));
    }

    public function create(): View
    {
        return view('admin.articles.form', ['article' => new Article, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, new Article);
    }

    public function edit(Article $article): View
    {
        abort_unless(request()->user()->canEditArticle($article), 403);

        return view('admin.articles.form', ['article' => $article, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        abort_unless($request->user()->canEditArticle($article), 403);

        return $this->save($request, $article);
    }

    public function shareOnFacebook(Request $request, Article $article): RedirectResponse
    {
        abort_unless($request->user()->canPublishArticles(), 403);

        if ($article->status !== 'published' || ! $article->published_at || $article->published_at->isFuture()) {
            return back()->withErrors(['facebook_post' => 'Only publicly published stories can be posted to Facebook.']);
        }

        if ($article->facebook_post_id) {
            return back()->with('status', 'This story has already been posted to Facebook.');
        }

        $queued = Article::query()
            ->whereKey($article->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNull('facebook_post_id')
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('facebook_post_status')
                ->orWhereNotIn('facebook_post_status', ['pending', 'publishing']))
            ->update([
                'facebook_post_status' => 'pending',
                'facebook_post_error' => null,
            ]);

        if ($queued !== 1) {
            $article->refresh();

            if ($article->facebook_post_id) {
                return back()->with('status', 'This story has already been posted to Facebook.');
            }

            if (in_array($article->facebook_post_status, ['pending', 'publishing'], true)) {
                return back()->with('status', 'A Facebook post for this story is already queued or in progress.');
            }

            return back()->withErrors(['facebook_post' => 'This story is no longer eligible for Facebook sharing.']);
        }

        dispatch((new PublishArticleToFacebook($article->id, $article->published_at->timestamp))->afterCommit());

        return back()->with('status', 'Facebook post queued. Its status will update when the publishing job completes.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        abort_unless(request()->user()->canManageNewsroom(), 403);

        if ($article->cover_image) {
            Storage::disk('public')->delete($article->cover_image);
        }

        $article->delete();

        return back()->with('status', 'Story deleted.');
    }

    private function save(Request $request, Article $article): RedirectResponse
    {
        $canPublish = $request->user()->canPublishArticles();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('articles', 'slug')->ignore($article->id), 'not_in:admin,live,category,newsletter,sitemap.xml,robots.txt,search,login'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'excerpt' => ['required', 'string', 'max:320'],
            'body' => ['required', 'string', 'min:20'],
            'status' => ['required', Rule::in($canPublish ? ['draft', 'published'] : ['draft'])],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'source_name' => ['nullable', 'string', 'max:180'],
            'source_url' => ['nullable', 'url:https', 'max:2048'],
            'video_url' => ['nullable', 'url:https', 'regex:~\Ahttps://(?:(?:www\.)?youtube\.com/watch\?v=|(?:www\.)?youtube\.com/shorts/|youtu\.be/|www\.youtube-nocookie\.com/embed/)([A-Za-z0-9_-]{11})(?:[?&][A-Za-z0-9_=&%-]+)?\z~', 'max:2048'],
            'image_alt' => ['nullable', 'string', 'max:180'],
            'image_caption' => ['nullable', 'string', 'max:255'],
            'image_credit' => ['nullable', 'string', 'max:255'],
            'image_credit_url' => ['nullable', 'url:https', 'max:2048'],
            'cover' => ['nullable', 'image', 'max:5120'],
            'featured' => [$canPublish ? 'sometimes' : 'prohibited', 'boolean'],
            'breaking' => [$canPublish ? 'sometimes' : 'prohibited', 'boolean'],
            'include_in_africa' => ['sometimes', 'boolean'],
            'published_at' => [$canPublish ? 'nullable' : 'prohibited', 'date'],
        ]);

        $slug = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        if (! $article->exists && Article::where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(6));
        }

        if ($request->hasFile('cover')) {
            $previousCover = $article->cover_image;
            $coverPath = Storage::disk('public')->putFile('articles', $request->file('cover'));

            if (! is_string($coverPath)) {
                throw new RuntimeException('Unable to store the story cover image.');
            }

            $data['cover_image'] = $coverPath;
        }

        $data['slug'] = $slug;
        $data['user_id'] = $article->exists ? $article->user_id : $request->user()->id;
        $data['meta_description'] = ($data['meta_description'] ?? null) ?: Str::limit($data['excerpt'], 160, '');
        $data['video_embed_url'] = null;
        if (isset($data['video_url']) && preg_match('~\Ahttps://(?:(?:www\.)?youtube\.com/watch\?v=|(?:www\.)?youtube\.com/shorts/|youtu\.be/|www\.youtube-nocookie\.com/embed/)([A-Za-z0-9_-]{11})~', $data['video_url'], $matches) === 1) {
            $data['video_embed_url'] = 'https://www.youtube-nocookie.com/embed/'.$matches[1];
        }
        $data['featured'] = $canPublish && $request->boolean('featured');
        $data['breaking'] = $canPublish && $request->boolean('breaking');
        $data['include_in_africa'] = Category::query()->whereKey($data['category_id'])->value('slug') === 'malawi'
            || $request->boolean('include_in_africa');
        $data['published_at'] = $canPublish && $data['status'] === 'published'
            ? ($data['published_at'] ?? $article->published_at ?? now())
            : null;
        unset($data['cover'], $data['video_url']);

        $article->fill($data)->save();

        if (isset($previousCover) && $previousCover) {
            Storage::disk('public')->delete($previousCover);
        }

        return redirect()->route('admin.articles.index')->with('status', 'Story saved.');
    }
}
