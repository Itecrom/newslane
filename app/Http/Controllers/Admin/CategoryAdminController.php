<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryAdminController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount('articles')->orderBy('position')->orderBy('name')->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'position' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'show_on_homepage' => ['sometimes', 'boolean'],
        ]);
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        if (Category::where('slug', $data['slug'])->exists()) {
            $data['slug'] .= '-'.Str::lower(Str::random(5));
        }
        $data['in_menu'] = $request->boolean('in_menu');
        $data['show_on_homepage'] = $request->boolean('show_on_homepage');
        Category::create($data);

        return back()->with('status', 'Category created.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('categories', 'slug')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'position' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'show_on_homepage' => ['sometimes', 'boolean'],
        ]);
        $data['in_menu'] = $request->boolean('in_menu');
        $data['show_on_homepage'] = $request->boolean('show_on_homepage');
        $category->update($data);

        return back()->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->articles()->exists()) {
            return back()->withErrors(['category' => 'Move or delete this category’s stories before deleting it.']);
        }

        $category->delete();

        return back()->with('status', 'Category deleted.');
    }
}
