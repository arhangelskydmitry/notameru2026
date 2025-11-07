@extends('frontend.layout')

@section('title', 'Автор: ' . $author->display_name . ' - Нота Миру')

@section('breadcrumbs')
    <a href="{{ route('home') }}">Главная</a>
    <span class="separator">›</span>
    <span>Автор</span>
    <span class="separator">›</span>
    <span class="current">{{ $author->display_name }}</span>
@endsection

@section('content')
<div style="display: grid; grid-template-columns: 3fr 1fr; gap: 30px; align-items: start;">
    <!-- Основной контент -->
    <div>
        <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #c80000;">
            <h1 style="font-size: 36px; color: #2c3e50;">
                ✍️ {{ $author->display_name }}
            </h1>
            @if($author->user_email)
                <p style="font-size: 16px; color: #666; margin-top: 10px;">
                    📧 {{ $author->user_email }}
                </p>
            @endif
            <p style="font-size: 14px; color: #999; margin-top: 10px;">
                {{ count($posts) }}+ {{ \App\Helpers\ContentHelper::pluralize(count($posts), ['статья', 'статьи', 'статей']) }}
            </p>
        </div>

        @if($posts->isEmpty())
            <div style="text-align: center; padding: 60px 20px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 48px; margin-bottom: 20px;">📝</div>
                <h3 style="color: #666;">У этого автора пока нет опубликованных статей</h3>
            </div>
        @else
            <div class="posts-grid" id="posts-container" data-author="{{ $author->ID }}">
                @foreach($posts as $post)
                    @include('partials.post-card', ['post' => $post])
                @endforeach
            </div>
            
            <!-- Индикатор загрузки -->
            <div id="loading-indicator" style="display: none; text-align: center; padding: 40px 0;">
                <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #c80000; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p style="margin-top: 15px; color: #666; font-size: 14px;">Загружаем еще новости...</p>
            </div>
            
            <!-- Триггер для автоматической подгрузки -->
            <div id="load-trigger" style="height: 1px;"></div>
            
            <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            </style>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                let offset = {{ count($posts) }};
                const limit = 6;
                let isLoading = false;
                let totalPostsReached = false;
                
                const postsContainer = document.getElementById('posts-container');
                const loadingIndicator = document.getElementById('loading-indicator');
                const loadTrigger = document.getElementById('load-trigger');
                const authorId = postsContainer.dataset.author;
                
                async function loadMorePosts() {
                    if (isLoading || totalPostsReached) return;
                    
                    isLoading = true;
                    loadingIndicator.style.display = 'block';
                    
                    try {
                        const response = await fetch(`/api/load-more-posts?offset=${offset}&limit=${limit}&author=${authorId}`);
                        const data = await response.json();
                        
                        if (data.html) {
                            postsContainer.insertAdjacentHTML('beforeend', data.html);
                            offset += limit;
                        }
                        
                        if (!data.hasMore) {
                            observer.disconnect();
                            loadTrigger.style.display = 'none';
                            totalPostsReached = true;
                        }
                    } catch (error) {
                        console.error('Ошибка загрузки постов:', error);
                    } finally {
                        isLoading = false;
                        loadingIndicator.style.display = 'none';
                    }
                }
                
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !isLoading && !totalPostsReached) {
                            loadMorePosts();
                        }
                    });
                }, {
                    rootMargin: '200px'
                });
                
                if (offset > 0) {
                    observer.observe(loadTrigger);
                }
            });
            </script>
        @endif
    </div>
    
    <!-- Сайдбар -->
    <aside style="position: sticky; top: 50px; align-self: start; max-height: calc(100vh - 60px); overflow-y: auto;">
        @include('partials.sidebar')
    </aside>
</div>
@endsection




