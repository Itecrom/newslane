<?php

use App\Http\Controllers\Admin\AdvertisementAdminController;
use App\Http\Controllers\Admin\ArticleAdminController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Admin\CommentAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LiveAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\SearchController;
use App\Models\Article;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

Route::get('/', HomeController::class)->name('home');
Route::get('/category/{category:slug}', [CategoryController::class, 'show'])->name('category.show');
Route::get('/live', [LiveController::class, 'index'])->name('live.index');
Route::get('/live/{event:slug}/updates', [LiveController::class, 'updates'])->name('live.updates');
Route::get('/live/{event:slug}', [LiveController::class, 'show'])->name('live.show');
Route::get('/search', SearchController::class)->name('search');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:3,10')->name('contact.store');
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:3,10')->name('newsletter.store');
Route::get('/newsletter/confirm/{subscriber}/{token}', [NewsletterController::class, 'confirm'])
    ->middleware(['signed', 'throttle:10,1'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{subscriber}/{token}', [NewsletterController::class, 'unsubscribe'])
    ->middleware(['signed', 'throttle:10,1'])->name('newsletter.unsubscribe');
Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

Route::get('/sitemap.xml', function () {
    $articles = Article::published()->latest('published_at')->get(['slug', 'updated_at']);
    $categories = \App\Models\Category::orderBy('position')->get(['slug']);

    return response()->view('sitemap', compact('articles', 'categories'))->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware(['guest', 'throttle:6,1'])->name('login.store');
    Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'editorial'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/articles', [ArticleAdminController::class, 'index'])->name('articles.index');
        Route::get('/articles/create', [ArticleAdminController::class, 'create'])->name('articles.create');
        Route::post('/articles', [ArticleAdminController::class, 'store'])->name('articles.store');
        Route::get('/articles/{article}/edit', [ArticleAdminController::class, 'edit'])->name('articles.edit');
        Route::put('/articles/{article}', [ArticleAdminController::class, 'update'])->name('articles.update');
        Route::delete('/articles/{article}', [ArticleAdminController::class, 'destroy'])->name('articles.destroy');
        Route::get('/categories', [CategoryAdminController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryAdminController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryAdminController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryAdminController::class, 'destroy'])->name('categories.destroy');
        Route::get('/comments', [CommentAdminController::class, 'index'])->name('comments.index');
        Route::patch('/comments/{comment}/approve', [CommentAdminController::class, 'approve'])->name('comments.approve');
        Route::delete('/comments/{comment}', [CommentAdminController::class, 'destroy'])->name('comments.destroy');
        Route::get('/advertisements', [AdvertisementAdminController::class, 'index'])->name('ads.index');
        Route::post('/advertisements', [AdvertisementAdminController::class, 'store'])->name('ads.store');
        Route::put('/advertisements/{advertisement}', [AdvertisementAdminController::class, 'update'])->name('ads.update');
        Route::delete('/advertisements/{advertisement}', [AdvertisementAdminController::class, 'destroy'])->name('ads.destroy');
        Route::get('/live', [LiveAdminController::class, 'index'])->name('live.index');
        Route::post('/live', [LiveAdminController::class, 'store'])->name('live.store');
        Route::patch('/live/{event}/close', [LiveAdminController::class, 'update'])->name('live.close');
        Route::post('/live/{event}/updates', [LiveAdminController::class, 'addUpdate'])->name('live.updates.store');
        Route::delete('/live/{event}', [LiveAdminController::class, 'destroy'])->name('live.destroy');
        Route::middleware('administrator')->group(function (): void {
            Route::get('/inquiries', [\App\Http\Controllers\Admin\ContactMessageAdminController::class, 'index'])->name('inquiries.index');
            Route::get('/inquiries/{contact}', [\App\Http\Controllers\Admin\ContactMessageAdminController::class, 'show'])->name('inquiries.show');
            Route::patch('/inquiries/{contact}', [\App\Http\Controllers\Admin\ContactMessageAdminController::class, 'update'])->name('inquiries.update');
            Route::get('/inquiries/{contact}/attachment', [\App\Http\Controllers\Admin\ContactMessageAdminController::class, 'attachment'])->name('inquiries.attachment');
            Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
            Route::post('/users', [UserAdminController::class, 'store'])->name('users.store');
            Route::patch('/users/{user}', [UserAdminController::class, 'update'])->name('users.update');
        });
    });
});

Route::post('/{article:slug}/comments', [ArticleController::class, 'comment'])->middleware('throttle:5,10')->name('articles.comments.store');
Route::post('/{article:slug}/like', [ArticleController::class, 'toggleLike'])->middleware('throttle:30,1')->name('articles.like');
Route::get('/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');
