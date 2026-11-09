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
        $featured = (clone $published)->where('featured', true)->latest('published_at')->first()
            ?? (clone $published)->latest('published_at')->first();
        $latest = (clone $published)->when($featured, fn ($query) => $query->whereKeyNot($featured->id))
            ->latest('published_at')->take(8)->get();
        $popular = (clone $published)->orderByDesc('views')->take(4)->get();
        $liveEvents = LiveEvent::where('status', 'live')->latest('started_at')->take(2)->get();
        $advertisements = Advertisement::available('homepage')->latest()->take(2)->get();

        return view('home', compact('breaking', 'featured', 'latest', 'popular', 'liveEvents', 'advertisements'));
    }
}
