@extends('layouts.admin')

@section('title', 'Categories | NEWSLANE')
@section('admin_title', 'Categories')

@section('admin_content')
    <div class="admin-content-grid category-layout">
        <section class="admin-panel"><div class="panel-heading"><div><p class="eyebrow">NAVIGATION & ORGANIZATION</p><h2>News categories</h2></div></div>
            <p class="muted-copy">Categories can appear in the main menu. Stories in a category share a public section page.</p>
            @forelse ($categories as $category)
                <form class="category-row" action="{{ route('admin.categories.update', $category) }}" method="post">@csrf @method('PUT')
                    <div class="category-fields"><label>Name<input name="name" value="{{ $category->name }}" required maxlength="100"></label><label>Slug<input name="slug" value="{{ $category->slug }}" required maxlength="120"></label><label>Menu position<input type="number" name="position" value="{{ $category->position }}" min="0"></label></div>
                    <label class="checkbox-label"><input type="checkbox" name="in_menu" value="1" @checked($category->in_menu)> Show in navigation</label>
                    <label>Description<input name="description" value="{{ $category->description }}" maxlength="500" placeholder="Optional section description"></label>
                    <div class="category-row-footer"><span>{{ $category->articles_count }} {{ Str::plural('story', $category->articles_count) }}</span><button class="small-action" type="submit">Save changes</button></div>
                </form>
            @empty <p class="admin-quiet">No categories yet. Add one to organize your reporting.</p> @endforelse
        </section>
        <section class="admin-panel category-create"><p class="eyebrow">NEW SECTION</p><h2>Add a category</h2><form action="{{ route('admin.categories.store') }}" method="post" class="stack-form">@csrf
            <label>Category name<input name="name" required maxlength="100" placeholder="e.g. Environment"></label>
            <label>URL slug <span class="field-help">Optional; generated from the name if empty.</span><input name="slug" maxlength="120" placeholder="environment"></label>
            <label>Description<textarea name="description" rows="3" maxlength="500" placeholder="A short introduction to this section"></textarea></label>
            <label>Menu position<input type="number" name="position" value="0" min="0" max="65535"></label>
            <label class="checkbox-label"><input type="checkbox" name="in_menu" value="1" checked> Show in navigation</label>
            <button class="button button-blue" type="submit">Create category <span>→</span></button>
        </form></section>
    </div>
@endsection
