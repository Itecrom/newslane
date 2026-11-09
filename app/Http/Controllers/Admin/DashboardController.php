<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\LiveEvent;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'published' => Article::where('status', 'published')->count(),
            'drafts' => Article::where('status', 'draft')->count(),
            'comments' => Comment::where('status', 'pending')->count(),
            'categories' => Category::count(),
            'live' => LiveEvent::where('status', 'live')->count(),
            'ads' => Advertisement::where('active', true)->count(),
        ];
        $articles = Article::with(['category', 'author'])->latest('updated_at')->take(7)->get();
        $comments = Comment::with('article')->where('status', 'pending')->latest()->take(6)->get();

        return view('admin.dashboard', compact('stats', 'articles', 'comments'));
    }
}
