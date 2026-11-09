<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function show(Category $category): View
    {
        $articles = Article::published()
            ->where('category_id', $category->id)
            ->with(['category', 'author'])
            ->withCount(['comments', 'likes'])
            ->latest('published_at')
            ->paginate(12);

        return view('category', compact('category', 'articles'));
    }
}
