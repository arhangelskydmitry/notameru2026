<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\RssController;
use App\Http\Controllers\Auth\AdminAuthController;

// Admin Authentication routes (без middleware)
Route::prefix('notaadmin')->group(function() {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

// Admin Panel routes (требуют аутентификации)
Route::prefix('notaadmin')->middleware('admin.auth')->group(function() {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'dashboard'])->name('admin.dashboard');
    
    // Content Quality Dashboard
    Route::get('/content-quality', [\App\Http\Controllers\Admin\DashboardController::class, 'contentQuality'])->name('admin.content-quality');
    
    // Posts management
    Route::get('/posts', [\App\Http\Controllers\Admin\PostAdminController::class, 'posts'])->name('admin.posts');
    Route::get('/posts/create', [\App\Http\Controllers\Admin\PostAdminController::class, 'createPost'])->name('admin.posts.create');
    Route::post('/posts/store', [\App\Http\Controllers\Admin\PostAdminController::class, 'storePost'])->name('admin.posts.store');
    Route::post('/posts/boost-views', [\App\Http\Controllers\Admin\PostAdminController::class, 'boostPostViews'])->name('admin.posts.boost-views');
    Route::get('/posts/{id}/edit', [\App\Http\Controllers\Admin\PostAdminController::class, 'editPost'])->name('admin.posts.edit');
    Route::post('/posts/{id}/update', [\App\Http\Controllers\Admin\PostAdminController::class, 'updatePost'])->name('admin.posts.update');
    Route::delete('/posts/{id}', [\App\Http\Controllers\Admin\PostAdminController::class, 'deletePost'])->name('admin.posts.delete');
    Route::post('/posts/upload-image', [\App\Http\Controllers\Admin\PostAdminController::class, 'uploadImage'])->name('admin.posts.upload-image');
    Route::post('/posts/generate-seo', [\App\Http\Controllers\Admin\PostAdminController::class, 'generateSeo'])->name('admin.posts.generate-seo');
    Route::post('/posts/rewrite-content', [\App\Http\Controllers\Admin\PostAdminController::class, 'rewriteContent'])->name('admin.posts.rewrite-content');

    
    // SEO AI Settings (только для суперадмина)
    Route::middleware('superadmin')->group(function () {
        Route::get('/seo-settings', [\App\Http\Controllers\Admin\SeoSettingsController::class, 'seoSettings'])->name('admin.seo-settings');
        Route::post('/seo-settings/update', [\App\Http\Controllers\Admin\SeoSettingsController::class, 'updateSeoSettings'])->name('admin.seo-settings.update');
        Route::post('/seo-settings/test-provider', [\App\Http\Controllers\Admin\SeoSettingsController::class, 'testSeoProvider'])->name('admin.seo-settings.test-provider');
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
    
    // Pages management (главный редактор и суперадмин)
    Route::middleware('editor')->group(function () {
        Route::get('/pages', [\App\Http\Controllers\Admin\PageAdminController::class, 'pages'])->name('admin.pages');
        Route::get('/pages/{id}/edit', [\App\Http\Controllers\Admin\PageAdminController::class, 'editPage'])->name('admin.pages.edit');
        Route::post('/pages/{id}/update', [\App\Http\Controllers\Admin\PageAdminController::class, 'updatePage'])->name('admin.pages.update');
        Route::post('/pages/{id}/delete', [\App\Http\Controllers\Admin\PageAdminController::class, 'deletePage'])->name('admin.pages.delete');
    });

    // Categories management (главный редактор и суперадмин)
    Route::middleware('editor')->group(function () {
        Route::get('/categories', [\App\Http\Controllers\Admin\TaxonomyAdminController::class, 'categories'])->name('admin.categories');
        Route::post('/categories/{id}/update', [\App\Http\Controllers\Admin\TaxonomyAdminController::class, 'updateCategory'])->name('admin.categories.update');
    });

    // Menu management (главный редактор и суперадмин)
    Route::middleware('editor')->group(function () {
        Route::get('/menu', [\App\Http\Controllers\Admin\TaxonomyAdminController::class, 'menu'])->name('admin.menu');
        Route::post('/menu/create', [\App\Http\Controllers\Admin\TaxonomyAdminController::class, 'createMenuItem'])->name('admin.menu.create');
        Route::post('/menu/{id}/update', [\App\Http\Controllers\Admin\TaxonomyAdminController::class, 'updateMenuItem'])->name('admin.menu.update');
        Route::post('/menu/{id}/delete', [\App\Http\Controllers\Admin\TaxonomyAdminController::class, 'deleteMenuItem'])->name('admin.menu.delete');
    });

    // Banners management (главный редактор и суперадмин)
    Route::middleware('editor')->group(function () {
        Route::get('/banners', [App\Http\Controllers\BannerController::class, 'index'])->name('admin.banners');
        Route::get('/banners/create', [App\Http\Controllers\BannerController::class, 'create'])->name('admin.banners.create');
        Route::post('/banners', [App\Http\Controllers\BannerController::class, 'store'])->name('admin.banners.store');
        Route::get('/banners/{id}/edit', [App\Http\Controllers\BannerController::class, 'edit'])->name('admin.banners.edit');
        Route::post('/banners/{id}', [App\Http\Controllers\BannerController::class, 'update'])->name('admin.banners.update');
        Route::post('/banners/{id}/delete', [App\Http\Controllers\BannerController::class, 'destroy'])->name('admin.banners.delete');
        Route::get('/banners/{id}/statistics', [App\Http\Controllers\BannerController::class, 'statistics'])->name('admin.banners.statistics');
        Route::post('/banners/{id}/toggle', [App\Http\Controllers\BannerController::class, 'toggleStatus'])->name('admin.banners.toggle');
        Route::get('/banners/{id}/preview', [App\Http\Controllers\BannerController::class, 'preview'])->name('admin.banners.preview');
    });

    // Users management (только суперадмин: смена ролей = эскалация привилегий)
    Route::middleware('superadmin')->group(function () {
        Route::get('/users', [\App\Http\Controllers\Admin\UserAdminController::class, 'users'])->name('admin.users');
        Route::get('/users/{id}/edit', [\App\Http\Controllers\Admin\UserAdminController::class, 'editUser'])->name('admin.users.edit');
        Route::post('/users/{id}/update', [\App\Http\Controllers\Admin\UserAdminController::class, 'updateUser'])->name('admin.users.update');
        Route::post('/users/{id}/impersonate', [\App\Http\Controllers\Admin\UserAdminController::class, 'impersonateUser'])
            ->name('admin.users.impersonate');
    });
    Route::post('/impersonation/stop', [\App\Http\Controllers\Admin\UserAdminController::class, 'stopImpersonation'])
        ->name('admin.users.impersonate.stop');

    // Press cards (главный редактор и суперадмин)
    Route::middleware('editor')->group(function () {
        Route::get('/press-cards', [\App\Http\Controllers\PressCardController::class, 'index'])->name('admin.press-cards.index');
        Route::get('/press-cards/create', [\App\Http\Controllers\PressCardController::class, 'create'])->name('admin.press-cards.create');
        Route::post('/press-cards', [\App\Http\Controllers\PressCardController::class, 'store'])->name('admin.press-cards.store');
        Route::get('/press-cards/{id}', [\App\Http\Controllers\PressCardController::class, 'show'])->name('admin.press-cards.show');
        Route::get('/press-cards/{id}/edit', [\App\Http\Controllers\PressCardController::class, 'edit'])->name('admin.press-cards.edit');
        Route::post('/press-cards/{id}/update', [\App\Http\Controllers\PressCardController::class, 'update'])->name('admin.press-cards.update');
        Route::post('/press-cards/{id}/revoke', [\App\Http\Controllers\PressCardController::class, 'revoke'])->name('admin.press-cards.revoke');
        Route::get('/press-cards/{id}/pdf', [\App\Http\Controllers\PressCardController::class, 'pdf'])->name('admin.press-cards.pdf');
        Route::get('/press-cards/{id}/preview', [\App\Http\Controllers\PressCardController::class, 'preview'])->name('admin.press-cards.preview');
    });
    
    // Activity log
    Route::get('/activity-log', [\App\Http\Controllers\Admin\DashboardController::class, 'activityLog'])->name('admin.activity-log');
    
    // Author statistics
    Route::get('/author-statistics', [\App\Http\Controllers\Admin\DashboardController::class, 'authorStatistics'])->name('admin.author-statistics');
    
    // My statistics (for authors)
    Route::get('/my-statistics', [\App\Http\Controllers\Admin\DashboardController::class, 'myStatistics'])->name('admin.my-statistics');
    
    // Profile
    Route::get('/profile', [\App\Http\Controllers\Admin\UserAdminController::class, 'profile'])->name('admin.profile');
    Route::post('/profile/update', [\App\Http\Controllers\Admin\UserAdminController::class, 'updateProfile'])->name('admin.profile.update');
    
    // Passwords management (только для суперадмина)
    Route::middleware('superadmin')->group(function () {
        Route::get('/passwords', [\App\Http\Controllers\Admin\UserAdminController::class, 'viewPasswords'])->name('admin.passwords');
        Route::post('/passwords/{id}/reset', [\App\Http\Controllers\Admin\UserAdminController::class, 'resetPassword'])->name('admin.passwords.reset');
    });
    
    // Sitemap management
    Route::get('/sitemap', [SitemapController::class, 'admin'])->name('admin.sitemap');
    Route::post('/sitemap/regenerate', [SitemapController::class, 'regenerate'])->name('admin.sitemap.regenerate');

    // Analytics dashboard
    Route::get('/analytics', [\App\Http\Controllers\Admin\DashboardController::class, 'analytics'])->name('admin.analytics');

    // Yandex services management (только для суперадмина)
    Route::middleware('superadmin')->group(function () {
        Route::get('/yandex', [\App\Http\Controllers\Admin\DashboardController::class, 'yandexServices'])->name('admin.yandex');
        Route::post('/yandex/update', [\App\Http\Controllers\Admin\DashboardController::class, 'updateYandexServices'])->name('admin.yandex.update');
        Route::get('/yandex/test-api', [\App\Http\Controllers\Admin\DashboardController::class, 'testYandexApi'])->name('admin.yandex.test-api');
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
Route::get('/social-image.jpg', [FrontendController::class, 'socialImageJpg'])->name('social.image.jpg');
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
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/api/sitemap/months/{year}', [FrontendController::class, 'getSitemapMonths'])->name('api.sitemap.months');
    Route::get('/api/sitemap/days/{year}/{month}', [FrontendController::class, 'getSitemapDays'])->name('api.sitemap.days');
    Route::get('/api/sitemap/posts/{year}/{month}/{day}', [FrontendController::class, 'getSitemapPosts'])->name('api.sitemap.posts');
});

Route::get('/search', [FrontendController::class, 'search'])->name('search');
Route::redirect('/kontakty', '/editorial', 301);
Route::redirect('/redakcziya', '/editorial', 301);
Route::redirect('/politika-konfidenczialnosti-persona', '/privacy', 301);
Route::redirect('/https-notame-ru-p9155previewtrue', '/yulij-cezar-live-yurij-grymov-predstavlyaet-svoego-shekspira', 301);
Route::get('/editorial', [FrontendController::class, 'editorialContacts'])->name('editorial');
Route::get('/advertising', [FrontendController::class, 'advertising'])->name('advertising');
Route::get('/privacy', function() {
    return view('frontend.privacy');
})->name('privacy');

Route::get('/press-verify/{cardNumber}', [\App\Http\Controllers\PressCardController::class, 'verify'])
    ->name('press.verify')
    ->where('cardNumber', 'NM-[0-9]{4}-[0-9]{4}');

// SEO routes
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/api/sitemap/load-year', [FrontendController::class, 'loadSitemapYear'])
    ->middleware('throttle:60,1')
    ->name('api.sitemap.year');
Route::get('/robots.txt', [SitemapController::class, 'robots']);

// Posts by date routes (должны быть ПЕРЕД catch-all роутом)
Route::get('/posts/{year}', function ($year) {
    return redirect()->route('posts.by-year', ['year' => $year], 301);
})->name('posts.year')->where('year', '[0-9]{4}');
Route::get('/posts/{year}/{month}', function ($year, $month) {
    return redirect()->route('posts.by-year-month', ['year' => $year, 'month' => $month], 301);
})->name('posts.month')->where(['year' => '[0-9]{4}', 'month' => '[0-9]{2}']);
Route::get('/posts/{year}/{month}/{day}', function ($year, $month, $day) {
    return redirect()->route('posts.by-date', ['date' => sprintf('%04d-%02d-%02d', $year, $month, $day)], 301);
})->name('posts.day')->where(['year' => '[0-9]{4}', 'month' => '[0-9]{2}', 'day' => '[0-9]{2}']);

// RSS feeds
Route::get('/feed', [RssController::class, 'standardRss'])->name('rss.feed');
Route::get('/feed/rambler', [RssController::class, 'ramblerNews'])->name('rss.rambler-news');
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

// TV-заставка с дашбордами (Mac mini, киоск-режим). Доступ по ключу WALLBOARD_KEY
Route::get('/wallboard', [\App\Http\Controllers\WallboardController::class, 'index'])->name('wallboard');
Route::get('/wallboard/data', [\App\Http\Controllers\WallboardController::class, 'data'])
    ->middleware('throttle:120,1')
    ->name('wallboard.data');

// Banner redirect с отслеживанием клика — ДО catch-all, иначе перехватится /{slug}
Route::get('/banner/redirect/{id}', [App\Http\Controllers\BannerController::class, 'redirect'])
    ->where('id', '[0-9]+')
    ->name('banner.redirect');

// Legacy gorod-magazine URL structure
Route::get('/{category}/{year}/{month}/{day}/{slug}', [FrontendController::class, 'legacyPost'])
    ->name('post.legacy')
    ->where([
        'year' => '[0-9]{4}',
        'month' => '[0-9]{2}',
        'day' => '[0-9]{2}',
        'category' => '^(?!api|admin|notaadmin|sitemap|robots|privacy|feed|yandex|banner|index\.php).*',
        'slug' => '.*',
    ]);

// Catch-all для постов (должен быть ПОСЛЕДНИМ)
Route::get('/{slug}', [FrontendController::class, 'post'])->name('post')->where('slug', '^(?!api|admin|notaadmin|sitemap|robots|privacy|editorial|advertising|feed|yandex|banner|index\.php).*');

// Служебные AJAX-эндпоинты фронтенда
Route::prefix('api')->middleware('throttle:120,1')->group(function() {
    // Lazy loading
    Route::get('/load-more-posts', [FrontendController::class, 'loadMorePosts']);
    
    // Smart search suggestions
    Route::get('/search-suggestions', [FrontendController::class, 'searchSuggestions']);

    // Legacy REST API → единый /api/v1 (301 для внешних потребителей)
    Route::get('/posts/latest', fn() => redirect('/api/v1/posts/latest', 301));
    Route::get('/posts/popular', fn(\Illuminate\Http\Request $r) => redirect('/api/v1/posts/popular?' . $r->getQueryString(), 301));
    Route::get('/posts', fn(\Illuminate\Http\Request $r) => redirect('/api/v1/posts?' . $r->getQueryString(), 301));
    Route::get('/posts/{id}', fn($id) => redirect(is_numeric($id) ? "/api/v1/posts/{$id}" : "/api/v1/posts/slug/{$id}", 301));
    Route::get('/categories', fn() => redirect('/api/v1/categories', 301));
    Route::get('/categories/{id}', fn($id) => redirect(is_numeric($id) ? "/api/v1/categories/{$id}" : "/api/v1/categories/slug/{$id}", 301));
    Route::get('/tags/popular', fn() => redirect('/api/v1/tags/popular', 301));
    Route::get('/tags', fn() => redirect('/api/v1/tags', 301));
    Route::get('/tags/{id}', fn($id) => redirect(is_numeric($id) ? "/api/v1/tags/{$id}" : "/api/v1/tags/slug/{$id}", 301));
});

// Banner tracking (БЕЗ CSRF проверки - вынесено из api группы)
Route::post('/api/banner/impression', [App\Http\Controllers\BannerController::class, 'trackImpression'])
    ->middleware('throttle:120,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    
Route::post('/api/banner/click', [App\Http\Controllers\BannerController::class, 'trackClick'])
    ->middleware('throttle:120,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Fallback route for 404 errors (ДОЛЖЕН БЫТЬ ПОСЛЕДНИМ!)
Route::fallback([FrontendController::class, 'notFound']);
