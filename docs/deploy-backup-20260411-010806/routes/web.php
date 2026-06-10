<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\RssController;
use App\Http\Controllers\Auth\AdminAuthController;

// Admin Authentication routes (без middleware)
Route::prefix('notaadmin')->group(function() {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

// Admin Panel routes (требуют аутентификации)
Route::prefix('notaadmin')->middleware('admin.auth')->group(function() {
    Route::get('/', [AdminPanelController::class, 'dashboard'])->name('admin.dashboard');
    
    // Content Quality Dashboard
    Route::get('/content-quality', [AdminPanelController::class, 'contentQuality'])->name('admin.content-quality');
    
    // Posts management
    Route::get('/posts', [AdminPanelController::class, 'posts'])->name('admin.posts');
    Route::get('/posts/create', [AdminPanelController::class, 'createPost'])->name('admin.posts.create');
    Route::post('/posts/store', [AdminPanelController::class, 'storePost'])->name('admin.posts.store');
    Route::post('/posts/boost-views', [AdminPanelController::class, 'boostPostViews'])->name('admin.posts.boost-views');
    Route::get('/posts/{id}/edit', [AdminPanelController::class, 'editPost'])->name('admin.posts.edit');
    Route::post('/posts/{id}/update', [AdminPanelController::class, 'updatePost'])->name('admin.posts.update');
    Route::delete('/posts/{id}', [AdminPanelController::class, 'deletePost'])->name('admin.posts.delete');
    Route::post('/posts/upload-image', [AdminPanelController::class, 'uploadImage'])->name('admin.posts.upload-image');
    Route::post('/posts/generate-seo', [AdminPanelController::class, 'generateSeo'])->name('admin.posts.generate-seo');
    Route::post('/posts/rewrite-content', [AdminPanelController::class, 'rewriteContent'])->name('admin.posts.rewrite-content');

    
    // SEO AI Settings (только для суперадмина)
    Route::middleware('superadmin')->group(function () {
        Route::get('/seo-settings', [AdminPanelController::class, 'seoSettings'])->name('admin.seo-settings');
        Route::post('/seo-settings/update', [AdminPanelController::class, 'updateSeoSettings'])->name('admin.seo-settings.update');
        Route::post('/seo-settings/test-provider', [AdminPanelController::class, 'testSeoProvider'])->name('admin.seo-settings.test-provider');
    });
    
    // SEO Analysis Tool (доступно главному редактору и суперадмину)
    Route::middleware('editor')->group(function () {
        Route::get('/seo-analysis', [\App\Http\Controllers\SeoAnalysisController::class, 'index'])->name('admin.seo-analysis');
        Route::get('/seo-analysis/analyze', [\App\Http\Controllers\SeoAnalysisController::class, 'analyze'])->name('admin.seo-analysis.analyze');
        Route::get('/seo-analysis/post/{id}', [\App\Http\Controllers\SeoAnalysisController::class, 'show'])->name('admin.seo-analysis.show');
        Route::post('/seo-analysis/preview', [\App\Http\Controllers\SeoAnalysisController::class, 'preview'])->name('admin.seo-analysis.preview');
        Route::post('/seo-analysis/apply', [\App\Http\Controllers\SeoAnalysisController::class, 'apply'])->name('admin.seo-analysis.apply');
        Route::post('/seo-analysis/batch-preview', [\App\Http\Controllers\SeoAnalysisController::class, 'batchPreview'])->name('admin.seo-analysis.batch-preview');
        Route::post('/seo-analysis/batch-apply', [\App\Http\Controllers\SeoAnalysisController::class, 'batchApply'])->name('admin.seo-analysis.batch-apply');
        Route::post('/seo-analysis/export-sql', [\App\Http\Controllers\SeoAnalysisController::class, 'exportSql'])->name('admin.seo-analysis.export-sql');
    });
    
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
    
    // Users management
    Route::get('/users', [AdminPanelController::class, 'users'])->name('admin.users');
    Route::get('/users/{id}/edit', [AdminPanelController::class, 'editUser'])->name('admin.users.edit');
    Route::post('/users/{id}/update', [AdminPanelController::class, 'updateUser'])->name('admin.users.update');
    Route::post('/users/{id}/impersonate', [AdminPanelController::class, 'impersonateUser'])
        ->name('admin.users.impersonate')
        ->middleware('superadmin');
    Route::post('/impersonation/stop', [AdminPanelController::class, 'stopImpersonation'])
        ->name('admin.users.impersonate.stop');
    
    // Activity log
    Route::get('/activity-log', [AdminPanelController::class, 'activityLog'])->name('admin.activity-log');
    
    // Author statistics
    Route::get('/author-statistics', [AdminPanelController::class, 'authorStatistics'])->name('admin.author-statistics');
    
    // My statistics (for authors)
    Route::get('/my-statistics', [AdminPanelController::class, 'myStatistics'])->name('admin.my-statistics');
    
    // Profile
    Route::get('/profile', [AdminPanelController::class, 'profile'])->name('admin.profile');
    Route::post('/profile/update', [AdminPanelController::class, 'updateProfile'])->name('admin.profile.update');
    
    // Passwords management (только для суперадмина)
    Route::middleware('superadmin')->group(function () {
        Route::get('/passwords', [AdminPanelController::class, 'viewPasswords'])->name('admin.passwords');
        Route::post('/passwords/{id}/reset', [AdminPanelController::class, 'resetPassword'])->name('admin.passwords.reset');
    });
    
    // Sitemap management
    Route::get('/sitemap', [SitemapController::class, 'admin'])->name('admin.sitemap');
    Route::post('/sitemap/regenerate', [SitemapController::class, 'regenerate'])->name('admin.sitemap.regenerate');

    // Analytics dashboard
    Route::get('/analytics', [AdminPanelController::class, 'analytics'])->name('admin.analytics');

    // Yandex services management (только для суперадмина)
    Route::middleware('superadmin')->group(function () {
        Route::get('/yandex', [AdminPanelController::class, 'yandexServices'])->name('admin.yandex');
        Route::post('/yandex/update', [AdminPanelController::class, 'updateYandexServices'])->name('admin.yandex.update');
        Route::get('/yandex/test-api', [AdminPanelController::class, 'testYandexApi'])->name('admin.yandex.test-api');
    });

    // Backups management (только для суперадмина)
    Route::middleware('superadmin')->prefix('backups')->name('admin.backups.')->group(function () {
        Route::get('/', [App\Http\Controllers\BackupController::class, 'index'])->name('index');
        Route::post('/create', [App\Http\Controllers\BackupController::class, 'create'])->name('create');
        Route::get('/{backup}/download', [App\Http\Controllers\BackupController::class, 'download'])->name('download');
        Route::delete('/{backup}', [App\Http\Controllers\BackupController::class, 'destroy'])->name('destroy');
        Route::get('/{backup}/restore-form', [App\Http\Controllers\BackupController::class, 'restoreForm'])->name('restore.form');
        Route::post('/{backup}/restore', [App\Http\Controllers\BackupController::class, 'restore'])->name('restore');
        Route::get('/{backup}/status', [App\Http\Controllers\BackupController::class, 'status'])->name('status');
        Route::post('/cleanup', [App\Http\Controllers\BackupController::class, 'cleanup'])->name('cleanup');
    });

    // Counters management (только для суперадмина)
    Route::middleware('superadmin')->prefix('counters')->name('admin.counters.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CounterController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\CounterController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\CounterController::class, 'store'])->name('store');
        Route::get('/{counter}/edit', [\App\Http\Controllers\CounterController::class, 'edit'])->name('edit');
        Route::put('/{counter}', [\App\Http\Controllers\CounterController::class, 'update'])->name('update');
        Route::delete('/{counter}', [\App\Http\Controllers\CounterController::class, 'destroy'])->name('destroy');
        Route::post('/{counter}/toggle', [\App\Http\Controllers\CounterController::class, 'toggleActive'])->name('toggle');
    });

    // Tags management (доступно главному редактору и суперадмину)
    Route::middleware('editor')->prefix('tags')->name('admin.tags.')->group(function () {
        Route::get('/', [\App\Http\Controllers\TagController::class, 'index'])->name('index');
        Route::get('/statistics', [\App\Http\Controllers\TagController::class, 'statistics'])->name('statistics');
        Route::get('/suggest', [\App\Http\Controllers\TagController::class, 'suggestTags'])->name('suggest');
        Route::get('/mass-auto-tagging', [\App\Http\Controllers\TagController::class, 'massAutoTagging'])->name('mass-auto-tagging');
        Route::post('/preview-auto-tagging', [\App\Http\Controllers\TagController::class, 'previewAutoTagging'])->name('preview-auto-tagging');
        Route::post('/{id}/preview-single-tagging', [\App\Http\Controllers\TagController::class, 'previewSingleTagging'])->name('preview-single-tagging');
        Route::post('/{id}/execute-auto-tagging', [\App\Http\Controllers\TagController::class, 'executeAutoTagging'])->name('execute-auto-tagging');
        Route::post('/bulk-create', [\App\Http\Controllers\TagController::class, 'bulkCreateFromSuggestions'])->name('bulk-create');
        Route::get('/create', [\App\Http\Controllers\TagController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\TagController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\TagController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\TagController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\TagController::class, 'destroy'])->name('destroy');
        Route::get('/merge', [\App\Http\Controllers\TagController::class, 'mergeForm'])->name('merge');
        Route::post('/merge', [\App\Http\Controllers\TagController::class, 'merge'])->name('merge.execute');
        Route::post('/bulk-delete', [\App\Http\Controllers\TagController::class, 'bulkDelete'])->name('bulk-delete');
        
        // Умное слияние дубликатов тегов
        Route::get('/merge-duplicates', [\App\Http\Controllers\TagMergeController::class, 'index'])->name('merge-index');
        Route::post('/merge-preview', [\App\Http\Controllers\TagMergeController::class, 'previewMerge'])->name('merge-preview');
        Route::post('/merge-execute', [\App\Http\Controllers\TagMergeController::class, 'executeMerge'])->name('merge-execute');
        Route::post('/merge-bulk', [\App\Http\Controllers\TagMergeController::class, 'bulkMerge'])->name('merge-bulk');
    });

    // 404 Logs (доступно главному редактору и суперадмину)
    Route::middleware('editor')->prefix('404-logs')->name('admin.404-logs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotFoundLogController::class, 'index'])->name('index');
        Route::get('/details', [\App\Http\Controllers\NotFoundLogController::class, 'details'])->name('details');
        Route::post('/cleanup', [\App\Http\Controllers\NotFoundLogController::class, 'cleanup'])->name('cleanup');
        Route::get('/export', [\App\Http\Controllers\NotFoundLogController::class, 'export'])->name('export');
    });

    // Meta Descriptions (доступно главному редактору и суперадмину)
    Route::middleware('editor')->prefix('meta-descriptions')->name('admin.meta-descriptions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MetaDescriptionController::class, 'index'])->name('index');
        Route::post('/preview', [\App\Http\Controllers\MetaDescriptionController::class, 'preview'])->name('preview');
        Route::post('/apply', [\App\Http\Controllers\MetaDescriptionController::class, 'apply'])->name('apply');
        Route::post('/bulk-generate', [\App\Http\Controllers\MetaDescriptionController::class, 'bulkGenerate'])->name('bulk-generate');
        Route::get('/export', [\App\Http\Controllers\MetaDescriptionController::class, 'export'])->name('export');
    });
});

// Frontend routes
Route::get('/', [FrontendController::class, 'index'])->name('home');
Route::get('/category/{slug}', [FrontendController::class, 'category'])->name('category');
Route::get('/tag/{slug}', [FrontendController::class, 'tag'])->name('tag');
Route::get('/author/{id}', [FrontendController::class, 'author'])->name('author');

// Карта сайта
Route::get('/sitemap-html', [FrontendController::class, 'htmlSitemap'])->name('sitemap.html');

// Посты по датам
Route::get('/date/{date}', [FrontendController::class, 'postsByDate'])->name('posts.by-date')->where('date', '\d{4}-\d{2}-\d{2}');
Route::get('/archive/{year}', [FrontendController::class, 'postsByYear'])->name('posts.by-year')->where('year', '\d{4}');
Route::get('/archive/{year}/{month}', [FrontendController::class, 'postsByYearMonth'])->name('posts.by-year-month')->where(['year' => '\d{4}', 'month' => '\d{2}']);

// API для карты сайта
Route::get('/api/sitemap/months/{year}', [FrontendController::class, 'getSitemapMonths'])->name('api.sitemap.months');
Route::get('/api/sitemap/days/{year}/{month}', [FrontendController::class, 'getSitemapDays'])->name('api.sitemap.days');
Route::get('/api/sitemap/posts/{year}/{month}/{day}', [FrontendController::class, 'getSitemapPosts'])->name('api.sitemap.posts');

Route::get('/search', [FrontendController::class, 'search'])->name('search');
Route::get('/privacy', function() {
    return view('frontend.privacy');
})->name('privacy');

// SEO routes
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/api/sitemap/load-year', [FrontendController::class, 'loadSitemapYear'])->name('api.sitemap.year');
Route::get('/robots.txt', [SitemapController::class, 'robots']);

// Posts by date routes (должны быть ПЕРЕД catch-all роутом)
Route::get('/posts/{year}', [FrontendController::class, 'postsByYear'])->name('posts.year')->where('year', '[0-9]{4}');
Route::get('/posts/{year}/{month}', [FrontendController::class, 'postsByMonth'])->name('posts.month')->where(['year' => '[0-9]{4}', 'month' => '[0-9]{2}']);
Route::get('/posts/{year}/{month}/{day}', [FrontendController::class, 'postsByDay'])->name('posts.day')->where(['year' => '[0-9]{4}', 'month' => '[0-9]{2}', 'day' => '[0-9]{2}']);

// RSS feeds
Route::get('/feed', [RssController::class, 'standardRss'])->name('rss.feed');
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
});

// Banner tracking (БЕЗ CSRF проверки - вынесено из api группы)
Route::post('/api/banner/impression', [App\Http\Controllers\BannerController::class, 'trackImpression'])
    ->middleware('throttle:120,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    
Route::post('/api/banner/click', [App\Http\Controllers\BannerController::class, 'trackClick'])
    ->middleware('throttle:120,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Banner redirect с отслеживанием клика (GET - не требует CSRF)
Route::get('/banner/redirect/{id}', [App\Http\Controllers\BannerController::class, 'redirect'])
    ->name('banner.redirect');

// News Sources management (только для суперадмина)
Route::middleware('superadmin')->group(function () {
    Route::get('/news-sources', [AdminPanelController::class, 'newsSources'])->name('admin.news-sources');
    Route::post('/news-sources', [AdminPanelController::class, 'storeNewsSource'])->name('admin.news-sources.store');
    Route::get('/news-sources/{id}/edit', [AdminPanelController::class, 'editNewsSource'])->name('admin.news-sources.edit');
    Route::post('/news-sources/{id}', [AdminPanelController::class, 'updateNewsSource'])->name('admin.news-sources.update');
    Route::get('/news-sources/{id}/delete', [AdminPanelController::class, 'deleteNewsSource'])->name('admin.news-sources.delete');
    Route::post('/news-sources/{id}/parse', [AdminPanelController::class, 'parseNewsSource'])->name('admin.news-sources.parse');
    Route::get('/parsed-articles', [AdminPanelController::class, 'parsedArticles'])->name('admin.parsed-articles');
    Route::post('/parsed-articles/{id}/generate', [AdminPanelController::class, 'generateParsedArticle'])->name('admin.parsed-articles.generate');
});

// Fallback route for 404 errors (ДОЛЖЕН БЫТЬ ПОСЛЕДНИМ!)
Route::fallback([FrontendController::class, 'notFound']);
