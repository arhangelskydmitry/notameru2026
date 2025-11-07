@extends('frontend.layout')

@section('title', 'Нота Миру - Новости звезд шоу-бизнеса, музыки и культуры')

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
                    $latestPosts = \App\Models\WordPress\Post::where('post_type', 'post')
                        ->where('post_status', 'publish')
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

<!-- Первый блок на всю ширину (без сайдбара) -->
<div class="home-first-block">
    <!-- Слайдер с последними новостями -->
    <div class="home-slider-wrapper">
        <!-- Заголовок слайдера -->
        <div style="background: #c80000; color: white; margin-bottom: 0; font-weight: 600; font-size: 16px; text-transform: uppercase; border-radius: 3px 3px 0 0; padding: 0 20px; height: 44px; display: flex; align-items: center;">
            Последние новости
        </div>
        
        <div class="main-slider" style="border-radius: 0 0 5px 5px; overflow: hidden; height: 100%;">
            @php
                $sliderPosts = $posts->take(5);
            @endphp
            
            @foreach($sliderPosts as $index => $post)
                @php
                    $thumbnailId = $post->getMeta('_thumbnail_id');
                    $thumbnail = null;
                    if ($thumbnailId) {
                        $attachment = \App\Models\WordPress\Post::find($thumbnailId);
                        if ($attachment) {
                            $thumbnail = $attachment->guid;
                        }
                    }
                @endphp
                
                <div class="slider-item {{ $index === 0 ? 'active' : '' }}">
                    @if($thumbnail)
                        <img src="{{ $thumbnail }}" alt="{{ $post->post_title }}">
                    @else
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                    @endif
                    
                    <div class="slider-caption">
                        @if($post->categories->isNotEmpty())
                            <div style="margin-bottom: 10px;">
                                @foreach($post->categories as $category)
                                    <a href="{{ route('category', $category->term->slug) }}" 
                                        style="background: #c80000; color: white; padding: 4px 12px; border-radius: 3px; font-size: 11px; text-transform: uppercase; text-decoration: none; display: inline-block; margin-right: 5px; margin-bottom: 5px;">
                                        {{ $category->term->name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        <h2>
                            <a href="{{ route('post', $post->post_name) }}">{{ $post->post_title }}</a>
                        </h2>
                        <div style="font-size: 13px; margin-top: 8px; opacity: 0.9;">
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
    
    <!-- Колонки: Интервью и Релизы -->
    <div class="home-sidebar-widgets">
        <!-- Интервью -->
        <div class="sidebar-widget">
            <h3 class="widget-title">Интервью</h3>
            <div class="widget-content">
                @php
                    $interviews = \App\Models\WordPress\Post::where('post_type', 'post')
                        ->where('post_status', 'publish')
                        ->whereHas('categories.term', function($q) {
                            $q->where('slug', 'interview');
                        })
                        ->orderBy('post_date', 'desc')
                        ->limit(2)
                        ->get();
                @endphp
                
                @foreach($interviews as $interview)
                    @php
                        $thumbnailId = $interview->getMeta('_thumbnail_id');
                        $thumbnail = null;
                        if ($thumbnailId) {
                            $attachment = \App\Models\WordPress\Post::find($thumbnailId);
                            if ($attachment) {
                                $thumbnail = $attachment->guid;
                            }
                        }
                    @endphp
                    
                    <div class="widget-post hover-lift">
                        @if($thumbnail)
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
        
        <!-- Баннер в сайдбаре (demo) -->
        <div class="sidebar-widget" style="text-align: center;">
            @banner('sidebar-top')
        </div>
        
        <!-- Релизы (flex-grow для заполнения оставшегося пространства) -->
        <div class="sidebar-widget releases-widget">
            <h3 class="widget-title">Релизы</h3>
            <div class="widget-content">
                @php
                    $releases = \App\Models\WordPress\Post::where('post_type', 'post')
                        ->where('post_status', 'publish')
                        ->whereHas('categories.term', function($q) {
                            $q->where('slug', 'music');
                        })
                        ->orderBy('post_date', 'desc')
                        ->limit(2)
                        ->get();
                @endphp
                
                @foreach($releases as $release)
                    @php
                        $thumbnailId = $release->getMeta('_thumbnail_id');
                        $thumbnail = null;
                        if ($thumbnailId) {
                            $attachment = \App\Models\WordPress\Post::find($thumbnailId);
                            if ($attachment) {
                                $thumbnail = $attachment->guid;
                            }
                        }
                    @endphp
                    
                    <div class="widget-post hover-lift">
                        @if($thumbnail)
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
    </div>
</div>

<!-- Баннер перед вторым блоком -->
<div style="margin-bottom: 5px; text-align: center;">
    @banner('content-top')
</div>

<!-- Второй блок: Все новости + Сайдбар -->
<div class="home-content-with-sidebar">
    <!-- Левая колонка: Все новости -->
    <div class="home-main-content">
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
    
    <!-- Правая колонка: Sticky сайдбар -->
    <aside class="home-sidebar-sticky">
        @include('partials.sidebar')
    </aside>
</div>

<style>
/* Первый блок на главной */
.home-first-block {
    display: grid;
    grid-template-columns: 3fr 1fr; /* Сделали правую колонку уже */
    gap: 30px;
    margin-bottom: 5px; /* Уменьшили с 10px до 5px */
    align-items: stretch; /* Изменили с start на stretch для одинаковой высоты */
}

.home-slider-wrapper {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.home-sidebar-widgets {
    display: flex;
    flex-direction: column;
    height: 100%; /* Занимаем всю высоту */
    gap: 0; /* Убрали отступ между блоками */
}

/* Уменьшаем расстояние между виджетами на 4px */
.home-sidebar-widgets > .sidebar-widget {
    margin-top: -4px;
}

.home-sidebar-widgets > .sidebar-widget:first-child {
    margin-top: 0; /* Первый виджет без отступа */
}

/* Выравнивание заголовков по высоте */
.home-first-block .widget-title {
    height: 44px;
    line-height: 44px;
    padding: 0 20px;
}

/* Отступы внутри виджетов */
.home-sidebar-widgets .widget-content {
    padding: 10px; /* Уменьшили с 15px до 10px */
}

.home-sidebar-widgets .widget-post {
    margin-bottom: 8px; /* Уменьшили с 15px до 8px */
    padding-bottom: 8px; /* Уменьшили с 15px до 8px */
    border-bottom: 1px solid #eee;
}

.home-sidebar-widgets .widget-post:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

/* Блок Интервью - размер по содержимому */
.home-sidebar-widgets > .sidebar-widget:first-child {
    flex: 0 0 auto; /* Не растягивается и не сжимается */
    display: flex;
    flex-direction: column;
}

.home-sidebar-widgets > .sidebar-widget:first-child .widget-content {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

/* Баннер - автоматическая высота */
.home-sidebar-widgets > .sidebar-widget:nth-child(2) {
    flex: 0 0 auto;
}

/* Блок Релизы - размер по содержимому */
.releases-widget {
    flex: 0 0 auto; /* Не растягивается и не сжимается */
    display: flex;
    flex-direction: column;
}

.releases-widget .widget-content {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

/* Второй блок: Все новости + Sticky сайдбар */
.home-content-with-sidebar {
    display: grid;
    grid-template-columns: 3fr 1fr; /* Сделали сайдбар уже */
    gap: 30px;
    align-items: start;
}

.home-main-content {
    min-width: 0;
}

/* Sticky сайдбар */
.home-sidebar-sticky {
    position: sticky;
    top: 50px; /* Уменьшили с 60px до 50px (поднято на 10px) */
    align-self: start;
    max-height: calc(100vh - 60px); /* Увеличили доступную высоту (было 70px) */
    overflow-y: auto; /* Прокрутка если контент длинный */
}

/* Убираем дефолтные стили скроллбара для сайдбара (незаметный скроллбар) */
.home-sidebar-sticky::-webkit-scrollbar {
    width: 4px; /* Тонкий скроллбар */
}

.home-sidebar-sticky::-webkit-scrollbar-track {
    background: transparent; /* Прозрачный фон */
}

.home-sidebar-sticky::-webkit-scrollbar-thumb {
    background: rgba(200, 0, 0, 0.2); /* Полупрозрачный красный */
    border-radius: 3px;
}

.home-sidebar-sticky::-webkit-scrollbar-thumb:hover {
    background: rgba(200, 0, 0, 0.4); /* Чуть ярче при наведении */
}

/* Для Firefox - тонкий скроллбар */
.home-sidebar-sticky {
    scrollbar-width: thin;
    scrollbar-color: rgba(200, 0, 0, 0.2) transparent;
}

/* Адаптация для мобильных устройств */
@media (max-width: 768px) {
    .home-first-block {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .home-slider-wrapper {
        height: auto;
    }
    
    .home-sidebar-widgets {
        height: auto;
        gap: 15px;
    }
    
    .home-content-with-sidebar {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .home-sidebar-sticky {
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
