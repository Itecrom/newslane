<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PublishArticleToFacebook;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Contracts\View\View;
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

        if ($article->status === 'published' && ! $article->facebook_post_id) {
            $pageId = config('services.facebook_page.page_id');
            $accessToken = config('services.facebook_page.page_access_token');
            $graphApiVersion = config('services.facebook_page.graph_api_version');
            $facebookReady = is_string($pageId) && ctype_digit($pageId)
                && is_string($accessToken) && $accessToken !== ''
                && is_string($graphApiVersion) && preg_match('/^v[0-9]+\.[0-9]+$/D', $graphApiVersion) === 1;

            if ($facebookReady) {
                $article->forceFill([
                    'facebook_post_status' => 'pending',
                    'facebook_post_error' => null,
                ])->save();

                $facebookJob = new PublishArticleToFacebook($article->id, $article->published_at->timestamp);

                if ($article->published_at?->isFuture()) {
                    $facebookJob->delay($article->published_at);
                }

                dispatch($facebookJob->afterCommit());
            } else {
                $article->forceFill([
                    'facebook_post_status' => 'not_configured',
                    'facebook_post_error' => 'Set a Facebook Page ID and Page access token in the application environment to enable automatic sharing.',
                ])->save();
            }
        }

        if (isset($previousCover) && $previousCover) {
            Storage::disk('public')->delete($previousCover);
        }

        return redirect()->route('admin.articles.index')->with('status', 'Story saved.');
    }
}
