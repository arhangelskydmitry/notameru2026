<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// WordPress API routes with rate limiting
Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    // Posts
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{id}', [PostController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/posts/slug/{slug}', [PostController::class, 'showBySlug']);
    
    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/categories/slug/{slug}', [CategoryController::class, 'showBySlug']);
    
    // Tags
    Route::get('/tags', [TagController::class, 'index']);
    Route::get('/tags/{id}', [TagController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/tags/slug/{slug}', [TagController::class, 'showBySlug']);
});

// Infinite scroll endpoint
Route::get('/load-more-posts', [\App\Http\Controllers\FrontendController::class, 'loadMorePosts']);

