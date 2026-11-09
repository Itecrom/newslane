<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ArticleAdminController extends Controller
{
    public function index(): View
    {
        $articles = Article::with(['category', 'author'])->latest('updated_at')->paginate(15);

        return view('admin.articles.index', compact('articles'));
    }

    public function create(): View
    {
        return view('admin.articles.form', ['article' => new Article(), 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, new Article());
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.form', ['article' => $article, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        return $this->save($request, $article);
    }

    public function destroy(Article $article): RedirectResponse
    {
        if ($article->cover_image) {
            Storage::disk('public')->delete($article->cover_image);
        }

        $article->delete();

        return back()->with('status', 'Story deleted.');
    }

    private function save(Request $request, Article $article): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('articles', 'slug')->ignore($article->id), 'not_in:admin,live,category,newsletter,sitemap.xml,robots.txt,search,login'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'excerpt' => ['required', 'string', 'max:320'],
            'body' => ['required', 'string', 'min:20'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'image_alt' => ['nullable', 'string', 'max:180'],
            'cover' => ['nullable', 'image', 'max:5120'],
            'featured' => ['sometimes', 'boolean'],
            'breaking' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        $slug = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        if (! $article->exists && Article::where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(6));
        }

        if ($request->hasFile('cover')) {
            $previousCover = $article->cover_image;
            $data['cover_image'] = Storage::disk('public')->putFile('articles', $request->file('cover'));
        }

        $data['slug'] = $slug;
        $data['user_id'] = $article->exists ? $article->user_id : $request->user()->id;
        $data['meta_description'] = ($data['meta_description'] ?? null) ?: Str::limit($data['excerpt'], 160, '');
        $data['featured'] = $request->boolean('featured');
        $data['breaking'] = $request->boolean('breaking');
        $data['published_at'] = $data['status'] === 'published'
            ? ($data['published_at'] ?? $article->published_at ?? now())
            : null;
        unset($data['cover']);

        $article->fill($data)->save();

        if (isset($previousCover) && $previousCover) {
            Storage::disk('public')->delete($previousCover);
        }

        return redirect()->route('admin.articles.index')->with('status', 'Story saved.');
    }
}
