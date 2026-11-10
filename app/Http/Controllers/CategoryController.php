<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function show(Category $category): View
    {
        $query = Article::published();

        if ($category->slug === 'africa') {
            $query->where(function ($query) use ($category): void {
                $query->where('include_in_africa', true)
                    ->orWhere('category_id', $category->id)
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', 'malawi'));
            });
        } else {
            $query->where('category_id', $category->id);
        }

        $articles = $query
            ->with(['category', 'author'])
            ->withCount(['comments', 'likes'])
            ->latest('published_at')
            ->paginate(12);

        return view('category', compact('category', 'articles'));
    }
}
