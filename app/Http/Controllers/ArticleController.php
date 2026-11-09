<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Advertisement;
use App\Models\Comment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function show(Request $request, Article $article): View
    {
        abort_unless($article->status === 'published' && $article->published_at?->lte(now()), 404);

        $article->increment('views');
        $article->load(['category', 'author'])
            ->loadCount([
                'likes',
                'comments as comments_count' => fn ($query) => $query->where('status', 'approved'),
            ]);
        $article->setRelation('comments', $article->comments()->where('status', 'approved')->latest()->take(30)->get());
        $visitor = $request->cookie('news_visitor');
        $liked = is_string($visitor) && $article->likes()
            ->where('visitor_hash', hash_hmac('sha256', $visitor, config('app.key')))
            ->exists();
        $related = Article::published()
            ->where('category_id', $article->category_id)
            ->whereKeyNot($article->id)
            ->with(['category', 'author'])
            ->latest('published_at')
            ->take(3)
            ->get();
        $advertisement = Advertisement::available('article')->inRandomOrder()->first();

        return view('article', compact('article', 'liked', 'related', 'advertisement'));
    }

    public function comment(Request $request, Article $article): RedirectResponse
    {
        abort_unless($article->status === 'published' && $article->published_at?->lte(now()), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'body' => ['required', 'string', 'min:3', 'max:2000'],
        ]);
        $article->comments()->create($data + ['status' => 'pending']);

        return back()->with('status', 'Thanks for joining the conversation. Your comment will appear after review.');
    }

    public function toggleLike(Request $request, Article $article): RedirectResponse
    {
        abort_unless($article->status === 'published' && $article->published_at?->lte(now()), 404);

        $visitor = $request->cookie('news_visitor');
        $newVisitor = ! is_string($visitor) || strlen($visitor) < 40;
        $visitor = $newVisitor ? Str::random(64) : $visitor;
        $visitorHash = hash_hmac('sha256', $visitor, config('app.key'));
        $like = $article->likes()->where('visitor_hash', $visitorHash)->first();

        if ($like) {
            $like->delete();
        } else {
            $article->likes()->firstOrCreate(['visitor_hash' => $visitorHash]);
        }

        $response = back()->with('status', $like ? 'Your like was removed.' : 'Thanks for supporting this story.');

        if ($newVisitor) {
            $response->withCookie(cookie('news_visitor', $visitor, 525600, '/', null, $request->isSecure(), true, false, 'Lax'));
        }

        return $response;
    }
}
