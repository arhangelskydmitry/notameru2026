@extends('frontend.layout')

@section('title', 'Нота Миру - Новости звезд шоу-бизнеса, музыки и культуры')
@section('description', 'Последние новости музыки, шоу-бизнеса и культуры. Интервью с артистами, обзоры концертов и релизов. Актуальные материалы о звездах и событиях индустрии.')

@section('ticker')
<!-- Бегущая строка с последними новостями (стиль WordPress NewsCard) -->
<div class="top-stories-bar">
    <div class="top-stories-wrap">
        <div class="top-stories-label">
            <div class="top-stories-icon">
                <span class="flash-dot"></span>
            </div>
            <span class="label-text">Лента</span>
        </div>
        <div class="top-stories-content">
            <div class="marquee">
                @php
                    $latestPosts = \App\Models\WordPress\Post::publiclyVisible()
                        ->orderBy('post_date', 'desc')
                        ->limit(10)
                        ->get();
                @endphp
                @foreach($latestPosts as $tickerPost)
                    <a href="{{ route('post', $tickerPost->post_name) }}">{{ $tickerPost->post_title }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
/* Бегущая строка */
.top-stories-bar {
    width: 100%;
    background: #f8f8f8;
    border-bottom: 1px solid #e5e5e5;
    position: relative;
    overflow: hidden;
    height: 43px;  /* Уменьшили с 45px до 43px */
    margin: 0;
    padding: 0;
}

.top-stories-wrap {
    display: flex;
    align-items: stretch;
    height: 100%;
    position: relative;
    max-width: 100%;
}

.top-stories-label {
    background: #d0d0d0;
    display: flex;
    align-items: center;
    justify-content: flex-end;  /* Выровняли по правому краю */
    padding: 0 25px 0 15px;
    position: relative;
    z-index: 10;
    min-width: 140px;
}

/* Скошенный правый край в противоположную сторону */
.top-stories-label:after {
    content: '';
    position: absolute;
    right: -20px;
    top: 0;
    bottom: 0;
    width: 40px;
    background: #d0d0d0;
    transform: skewX(20deg);
}

.top-stories-icon {
    margin-left: 8px;  /* Изменили с margin-right на margin-left */
    order: 2;  /* Кружок идёт после текста */
    position: relative;
    z-index: 2;
}

.flash-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    background: #c80000;
    border-radius: 50%;
    animation: flash-animation 1s infinite;
}

@keyframes flash-animation {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.label-text {
    font-weight: 700;
    font-size: 14px;
    text-transform: uppercase;
    color: #333;
    position: relative;
    z-index: 2;
    order: 1;  /* Текст идёт первым */
}

.top-stories-content {
    flex: 1;
    overflow: hidden;
    position: relative;
    padding-left: 30px;
    display: flex;
    align-items: center;
}

/* Градиентная маска справа для плавного появления */
.top-stories-content:before {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 100px;
    background: linear-gradient(to left, #f8f8f8, transparent);
    z-index: 5;
    pointer-events: none;
}

.marquee {
    white-space: nowrap;
    display: inline-block;
}

.marquee a {
    display: inline-block;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    margin-right: 60px;
    transition: color 0.3s ease;
}

.marquee a:hover {
    color: #c80000;
}
</style>
@endsection

@section('content')

<!-- Единая сетка на всю страницу: Левая колонка (контент) + Правая колонка (сайдбар) -->
<div class="home-page-grid">
    <!-- ЛЕВАЯ КОЛОНКА: Слайдер + Все новости -->
    <div class="home-main-column">
        <!-- Блок 1: Слайдер с последними новостями -->
        <div class="home-slider-wrapper">
            <!-- Заголовок слайдера -->
            <h1 class="home-main-heading" style="background: #c80000; color: white; margin: 0; margin-bottom: 0; font-weight: 600; font-size: 16px; text-transform: uppercase; border-radius: 3px 3px 0 0; padding: 0 20px; height: 44px; display: flex; align-items: center;">
                Последние новости
            </h1>
            
            <div class="main-slider" style="border-radius: 0 0 5px 5px; overflow: hidden; height: 100%;">
                @php
                    $sliderPosts = $posts->take(5);
                @endphp
                
                @foreach($sliderPosts as $index => $post)
                    @php
                        $thumbnail = \App\Helpers\ContentHelper::getFeaturedImage($post, 'medium');
                    @endphp
                    
                    <div class="slider-item {{ $index === 0 ? 'active' : '' }}">
                        <a href="{{ route('post', $post->post_name) }}" class="slider-image-link">
                            @if($thumbnail && !str_contains($thumbnail, 'placeholder'))
                                <img src="{{ $thumbnail }}" alt="{{ $post->post_title }}">
                            @else
                                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                            @endif
                        </a>
                        
                        <div class="slider-caption">
                            @if($post->categories->isNotEmpty())
                                <div style="margin-bottom: 10px;">
                                    @foreach($post->categories as $category)
                                        <a href="{{ route('category', $category->term->slug) }}" 
                                            style="background: #c80000; color: white; padding: 4px 12px; border-radius: 3px; font-size: 11px; text-transform: uppercase; text-decoration: none; display: inline-block; margin-right: 5px; margin-bottom: 5px; box-shadow: 0 2px 6px rgba(0,0,0,0.4);">
                                            {{ $category->term->name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                            <h2>
                                <a href="{{ route('post', $post->post_name) }}">{{ $post->post_title }}</a>
                            </h2>
                            <div style="font-size: 13px; margin-top: 8px; opacity: 0.9; text-shadow: 0 1px 4px rgba(0,0,0,0.5);">
                                {{ $post->post_date->format('d.m.Y') }} • 👁 {{ $post->getMeta('post_views_count', 0) }}
                            </div>
                        </div>
                    </div>
                @endforeach
                
                @if($sliderPosts->count() > 1)
                    <div class="slider-controls">
                        @foreach($sliderPosts as $index => $post)
                            <div class="slider-dot {{ $index === 0 ? 'active' : '' }}"></div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Баннер между слайдером и новостями -->
        <div style="margin: 20px 0; text-align: center;">
            @banner('content-top')
        </div>
        
        <!-- Блок 2: Все новости -->
        <div class="home-all-news">
            <h2 style="font-size: 24px; margin-top: 0; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 3px solid #c80000; color: #222;">
                Все новости
            </h2>
            
            <div class="posts-grid" id="posts-container">
                @foreach($posts->skip(5) as $post)
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
        </div>
    </div>
    
    <!-- ПРАВАЯ КОЛОНКА: Единый сайдбар (Интервью, Баннер, Релизы, Популярное, Календарь и т.д.) -->
    <aside class="home-sidebar-column">
        <!-- Интервью -->
        <!-- Интервью -->
        <div class="sidebar-widget">
            <h3 class="widget-title">Интервью</h3>
            <div class="widget-content">
                @php
                    $interviews = \App\Models\WordPress\Post::publiclyVisible()
                        ->whereHas('categories.term', function($q) {
                            $q->where('slug', 'interview');
                        })
                        ->orderBy('post_date', 'desc')
                        ->limit(2)
                        ->get();
                @endphp
                
                @foreach($interviews as $interview)
                    @php
                        $thumbnail = \App\Helpers\ContentHelper::getFeaturedImage($interview, 'small');
                    @endphp
                    
                    <div class="widget-post hover-lift">
                        @if($thumbnail && !str_contains($thumbnail, 'placeholder'))
                            <a href="{{ route('post', $interview->post_name) }}">
                                <img src="{{ $thumbnail }}" alt="{{ $interview->post_title }}" class="widget-post-thumb">
                            </a>
                        @else
                            <a href="{{ route('post', $interview->post_name) }}">
                                <div class="widget-post-thumb" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                            </a>
                        @endif
                        <div class="widget-post-content">
                            <h4><a href="{{ route('post', $interview->post_name) }}">{{ Str::limit($interview->post_title, 70) }}</a></h4>
                            <div class="widget-post-meta">{{ $interview->post_date->format('d.m.Y') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Баннер -->
        <div class="sidebar-widget" style="text-align: center;">
            @banner('sidebar-top')
        </div>
        
        <!-- Релизы -->
        <div class="sidebar-widget releases-widget">
            <h3 class="widget-title">Релизы</h3>
            <div class="widget-content">
                @php
                    $releases = \App\Models\WordPress\Post::publiclyVisible()
                        ->whereHas('categories.term', function($q) {
                            $q->where('slug', 'music');
                        })
                        ->orderBy('post_date', 'desc')
                        ->limit(2)
                        ->get();
                @endphp
                
                @foreach($releases as $release)
                    @php
                        $thumbnail = \App\Helpers\ContentHelper::getFeaturedImage($release, 'small');
                    @endphp
                    
                    <div class="widget-post hover-lift">
                        @if($thumbnail && !str_contains($thumbnail, 'placeholder'))
                            <a href="{{ route('post', $release->post_name) }}">
                                <img src="{{ $thumbnail }}" alt="{{ $release->post_title }}" class="widget-post-thumb">
                            </a>
                        @else
                            <a href="{{ route('post', $release->post_name) }}">
                                <div class="widget-post-thumb" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);"></div>
                            </a>
                        @endif
                        <div class="widget-post-content">
                            <h4><a href="{{ route('post', $release->post_name) }}">{{ Str::limit($release->post_title, 70) }}</a></h4>
                            <div class="widget-post-meta">{{ $release->post_date->format('d.m.Y') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Остальные виджеты сайдбара (Популярное, Календарь и т.д.) -->
        @include('partials.sidebar')
    </aside>
</div>

<style>
/* Единая сетка на всю страницу - две независимые вертикальные колонки */
.home-page-grid {
    display: grid;
    grid-template-columns: 3fr 1fr; /* Левая колонка (контент) шире, правая - сайдбар */
    gap: 30px;
    align-items: start; /* Колонки независимы по высоте - это ключевое! */
}

/* Левая колонка - весь контент (слайдер + все новости) */
.home-main-column {
    min-width: 0; /* Для корректной работы grid */
}

/* Слайдер */
.home-slider-wrapper {
    margin-bottom: 20px; /* Отступ после слайдера */
}

/* Блок "Все новости" */
.home-all-news {
    /* margin-top управляется внутри блока через h2 */
}

/* Правая колонка - единый сайдбар (Интервью, Баннер, Релизы, Популярное и т.д.) */
.home-sidebar-column {
    position: sticky;
    top: 20px;
    align-self: start;
    max-height: calc(100vh - 30px);
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(200, 0, 0, 0.2) transparent;
}

/* Стили скроллбара для правой колонки */
.home-sidebar-column::-webkit-scrollbar {
    width: 4px;
}

.home-sidebar-column::-webkit-scrollbar-track {
    background: transparent;
}

.home-sidebar-column::-webkit-scrollbar-thumb {
    background: rgba(200, 0, 0, 0.2);
    border-radius: 3px;
}

.home-sidebar-column::-webkit-scrollbar-thumb:hover {
    background: rgba(200, 0, 0, 0.4);
}

/* Адаптация для мобильных */
@media (max-width: 768px) {
    .home-page-grid {
        grid-template-columns: 1fr; /* Одна колонка */
        gap: 30px;
    }
    
    .home-sidebar-column {
        position: static;
        max-height: none;
        overflow-y: visible;
    }
}

/* Анимация hover для виджетов */
.hover-lift {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let offset = {{ count($posts) }}; // Начальное количество загруженных постов
    const totalPosts = {{ $totalPosts }};
    const limit = 6; // Количество постов за одну подгрузку
    let isLoading = false;
    
    const postsContainer = document.getElementById('posts-container');
    const loadingIndicator = document.getElementById('loading-indicator');
    const loadTrigger = document.getElementById('load-trigger');
    
    // Функция загрузки дополнительных постов
    async function loadMorePosts() {
        if (isLoading || offset >= totalPosts) return;
        
        isLoading = true;
        loadingIndicator.style.display = 'block';
        
        try {
            const response = await fetch(`/api/load-more-posts?offset=${offset}&limit=${limit}`);
            const data = await response.json();
            
            if (data.html) {
                // Добавляем новые посты в контейнер
                postsContainer.insertAdjacentHTML('beforeend', data.html);
                offset += limit;
            }
            
            // Если больше нет постов, отключаем Observer
            if (!data.hasMore) {
                observer.disconnect();
                loadTrigger.style.display = 'none';
            }
        } catch (error) {
            console.error('Ошибка загрузки постов:', error);
        } finally {
            isLoading = false;
            loadingIndicator.style.display = 'none';
        }
    }
    
    // Intersection Observer для автоматической подгрузки
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !isLoading && offset < totalPosts) {
                loadMorePosts();
            }
        });
    }, {
        rootMargin: '200px' // Начинаем загрузку за 200px до триггера
    });
    
    // Наблюдаем за триггером
    if (offset < totalPosts) {
        observer.observe(loadTrigger);
    } else {
        loadTrigger.style.display = 'none';
    }
});
</script>
@endsection
