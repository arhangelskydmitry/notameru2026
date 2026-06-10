<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mac\AuthController;
use App\Http\Controllers\Api\Mac\PostController;
use App\Http\Controllers\Api\Mac\PressCardApiController;
use App\Http\Controllers\Api\Mac\UserController;
use App\Http\Controllers\Api\Mac\AssistantController;

Route::prefix('mac/v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('mac.auth')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/posts', [PostController::class, 'index']);
        Route::get('/posts/{id}', [PostController::class, 'show'])->where('id', '[0-9]+');
        Route::post('/posts', [PostController::class, 'store']);
        Route::put('/posts/{id}', [PostController::class, 'update'])->where('id', '[0-9]+');
        Route::patch('/posts/{id}/status', [PostController::class, 'updateStatus'])->where('id', '[0-9]+');
        Route::post('/posts/seo/generate', [PostController::class, 'generateSeo']);
        Route::post('/posts/summarize', [PostController::class, 'summarize']);
        Route::get('/categories', [PostController::class, 'categories']);

        Route::get('/press-cards', [PressCardApiController::class, 'index']);
        Route::post('/press-cards', [PressCardApiController::class, 'store']);
        Route::get('/press-cards/journalists', [PressCardApiController::class, 'journalists']);
        Route::get('/press-cards/{id}/pdf', [PressCardApiController::class, 'pdf'])->where('id', '[0-9]+');

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::patch('/users/{id}/active', [UserController::class, 'setActive'])->where('id', '[0-9]+');

        Route::post('/assistant/chat', [AssistantController::class, 'chat']);
    });
});

// Legacy public API
Route::get('/user', function (\Illuminate\Http\Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    Route::get('/posts', [\App\Http\Controllers\Api\PostController::class, 'index']);
    Route::get('/posts/{id}', [\App\Http\Controllers\Api\PostController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/posts/slug/{slug}', [\App\Http\Controllers\Api\PostController::class, 'showBySlug']);
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/categories/slug/{slug}', [\App\Http\Controllers\Api\CategoryController::class, 'showBySlug']);
    Route::get('/tags', [\App\Http\Controllers\Api\TagController::class, 'index']);
    Route::get('/tags/{id}', [\App\Http\Controllers\Api\TagController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/tags/slug/{slug}', [\App\Http\Controllers\Api\TagController::class, 'showBySlug']);
});

Route::get('/load-more-posts', [\App\Http\Controllers\FrontendController::class, 'loadMorePosts']);
