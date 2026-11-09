<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Article;
use App\Models\LiveEvent;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $published = Article::published()->with(['category', 'author'])->withCount(['comments', 'likes']);
        $breaking = (clone $published)->where('breaking', true)->latest('published_at')->take(5)->get();
        $featuredStories = (clone $published)->where('featured', true)->latest('published_at')->take(3)->get();
        if ($featuredStories->count() < 3) {
            $moreStories = (clone $published)
                ->whereNotIn('id', $featuredStories->modelKeys())
                ->latest('published_at')
                ->take(3 - $featuredStories->count())
                ->get();
            $featuredStories = $featuredStories->concat($moreStories);
        }
        $latest = (clone $published)->whereNotIn('id', $featuredStories->modelKeys())
            ->latest('published_at')->take(8)->get();
        $homeSections = \App\Models\Category::where('show_on_homepage', true)
            ->orderBy('position')
            ->with(['articles' => fn ($query) => $query->published()
                ->with(['category', 'author'])
                ->withCount(['comments as comments_count' => fn ($comments) => $comments->where('status', 'approved')])
                ->latest('published_at')
                ->take(4)])
            ->get()
            ->filter(fn ($category) => $category->articles->isNotEmpty());
        $videoEvents = LiveEvent::where('status', 'live')->whereNotNull('video_embed_url')->latest('started_at')->take(2)->get();
        $popular = (clone $published)->orderByDesc('views')->take(4)->get();
        $liveEvents = LiveEvent::where('status', 'live')->latest('started_at')->take(2)->get();
        $advertisements = Advertisement::available('homepage')->latest()->take(2)->get();

        return view('home', compact('breaking', 'featuredStories', 'latest', 'homeSections', 'videoEvents', 'popular', 'liveEvents', 'advertisements'));
    }
}
