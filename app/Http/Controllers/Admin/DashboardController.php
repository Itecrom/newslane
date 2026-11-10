<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\LiveEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (! auth()->user()->canManageNewsroom()) {
            return redirect()->route('admin.articles.index');
        }

        $stats = [
            'published' => Article::where('status', 'published')->count(),
            'drafts' => Article::where('status', 'draft')->count(),
            'comments' => Comment::where('status', 'pending')->count(),
            'categories' => Category::count(),
            'live' => LiveEvent::where('status', 'live')->count(),
            'ads' => Advertisement::where('active', true)->count(),
            'inquiries' => auth()->user()->isAdmin() ? ContactMessage::where('status', 'new')->count() : 0,
        ];
        $articles = Article::with(['category', 'author'])->latest('updated_at')->take(7)->get();
        $comments = Comment::with('article')->where('status', 'pending')->latest()->take(6)->get();

        return view('admin.dashboard', compact('stats', 'articles', 'comments'));
    }
}
