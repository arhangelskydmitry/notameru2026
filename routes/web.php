<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\RssController;

// Admin Panel routes (custom admin interface)
Route::prefix('notaadmin')->group(function() {
    Route::get('/', [AdminPanelController::class, 'dashboard'])->name('admin.dashboard');
    
    // Posts management
    Route::get('/posts', [AdminPanelController::class, 'posts'])->name('admin.posts');
    Route::get('/posts/{id}/edit', [AdminPanelController::class, 'editPost'])->name('admin.posts.edit');
    Route::post('/posts/{id}/update', [AdminPanelController::class, 'updatePost'])->name('admin.posts.update');
    Route::get('/posts/{id}/delete', [AdminPanelController::class, 'deletePost'])->name('admin.posts.delete');
    
    // Pages management
    Route::get('/pages', [AdminPanelController::class, 'pages'])->name('admin.pages');
    Route::get('/pages/{id}/edit', [AdminPanelController::class, 'editPage'])->name('admin.pages.edit');
    Route::post('/pages/{id}/update', [AdminPanelController::class, 'updatePage'])->name('admin.pages.update');
    Route::get('/pages/{id}/delete', [AdminPanelController::class, 'deletePage'])->name('admin.pages.delete');
    
    // Categories management
    Route::get('/categories', [AdminPanelController::class, 'categories'])->name('admin.categories');
    Route::post('/categories/{id}/update', [AdminPanelController::class, 'updateCategory'])->name('admin.categories.update');
    
    // Menu management
    Route::get('/menu', [AdminPanelController::class, 'menu'])->name('admin.menu');
    Route::post('/menu/create', [AdminPanelController::class, 'createMenuItem'])->name('admin.menu.create');
    Route::post('/menu/{id}/update', [AdminPanelController::class, 'updateMenuItem'])->name('admin.menu.update');
    Route::get('/menu/{id}/delete', [AdminPanelController::class, 'deleteMenuItem'])->name('admin.menu.delete');
    
    // SEO Dashboard
    Route::get('/seo', function() {
        return view('admin.seo-dashboard');
    })->name('admin.seo');
    
    // Banners management
    Route::get('/banners', [App\Http\Controllers\BannerController::class, 'index'])->name('admin.banners');
    Route::get('/banners/create', [App\Http\Controllers\BannerController::class, 'create'])->name('admin.banners.create');
    Route::post('/banners', [App\Http\Controllers\BannerController::class, 'store'])->name('admin.banners.store');
    Route::get('/banners/{id}/edit', [App\Http\Controllers\BannerController::class, 'edit'])->name('admin.banners.edit');
    Route::post('/banners/{id}', [App\Http\Controllers\BannerController::class, 'update'])->name('admin.banners.update');
    Route::get('/banners/{id}/delete', [App\Http\Controllers\BannerController::class, 'destroy'])->name('admin.banners.delete');
    Route::get('/banners/{id}/statistics', [App\Http\Controllers\BannerController::class, 'statistics'])->name('admin.banners.statistics');
    Route::get('/banners/{id}/toggle', [App\Http\Controllers\BannerController::class, 'toggleStatus'])->name('admin.banners.toggle');
    Route::get('/banners/{id}/preview', [App\Http\Controllers\BannerController::class, 'preview'])->name('admin.banners.preview');
});

// Frontend routes
Route::get('/', [FrontendController::class, 'index'])->name('home');
Route::get('/category/{slug}', [FrontendController::class, 'category'])->name('category');
Route::get('/tag/{slug}', [FrontendController::class, 'tag'])->name('tag');
Route::get('/author/{id}', [FrontendController::class, 'author'])->name('author');
Route::get('/search', [FrontendController::class, 'search'])->name('search');
Route::get('/privacy', function() {
    return view('frontend.privacy');
})->name('privacy');

// SEO routes
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);

// RSS feeds
Route::get('/feed/zen1', [RssController::class, 'yandexZen'])->name('rss.yandex-zen');
Route::get('/feed/yandex-zen', [RssController::class, 'yandexZen']); // Альтернативный URL

// Яндекс.Новости
Route::get('/yandex/news', [RssController::class, 'yandexNews'])->name('rss.yandex-news');
Route::get('/index.php', function() {
    if (request('yandex_feed') === 'news') {
        return app(RssController::class)->yandexNews();
    }
    return redirect('/');
});

// Яндекс.Турбо
Route::get('/yandex/turbo', [RssController::class, 'yandexTurbo'])->name('rss.yandex-turbo');

// Catch-all для постов (должен быть ПОСЛЕДНИМ)
Route::get('/{slug}', [FrontendController::class, 'post'])->name('post')->where('slug', '^(?!api|admin|notaadmin|sitemap|robots|privacy|feed|yandex|index\.php).*');

// API routes with rate limiting
Route::prefix('api')->middleware('throttle:120,1')->group(function() {
    // Posts
    Route::get('/posts', [\App\Http\Controllers\Api\PostController::class, 'index']);
    Route::get('/posts/latest', [\App\Http\Controllers\Api\PostController::class, 'latest']);
    Route::get('/posts/popular', [\App\Http\Controllers\Api\PostController::class, 'popular']);
    Route::get('/posts/{id}', [\App\Http\Controllers\Api\PostController::class, 'show']);
    
    // Categories
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);
    
    // Tags
    Route::get('/tags', [\App\Http\Controllers\Api\TagController::class, 'index']);
    Route::get('/tags/popular', [\App\Http\Controllers\Api\TagController::class, 'popular']);
    Route::get('/tags/{id}', [\App\Http\Controllers\Api\TagController::class, 'show']);
    
    // Lazy loading
    Route::get('/load-more-posts', [FrontendController::class, 'loadMorePosts']);
    
    // Smart search suggestions
    Route::get('/search-suggestions', [FrontendController::class, 'searchSuggestions']);
    
    // Banner tracking
    Route::post('/banner/impression', [App\Http\Controllers\BannerController::class, 'trackImpression']);
    Route::post('/banner/click', [App\Http\Controllers\BannerController::class, 'trackClick']);
});
