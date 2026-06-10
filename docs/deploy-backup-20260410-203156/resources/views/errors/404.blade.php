@extends('frontend.layout')

@section('title', 'Страница не найдена - 404 | Нота Миру')
@section('description', 'К сожалению, запрашиваемая страница не найдена.')
@section('robots', 'noindex, follow')

@php
    // Загружаем данные прямо в view, так как 404 может быть вызван Laravel напрямую
    $posts = \App\Models\WordPress\Post::where('post_type', 'post')
        ->where('post_status', 'publish')
        ->with(['author', 'categories.term'])
        ->orderBy('post_date', 'desc')
        ->limit(12)
        ->get();
    
    $totalPosts = \App\Models\WordPress\Post::where('post_type', 'post')
        ->where('post_status', 'publish')
        ->count();
@endphp

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
    height: 43px;
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
    justify-content: flex-end;
    padding: 0 25px 0 15px;
    position: relative;
    z-index: 10;
    min-width: 140px;
}

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
    margin-left: 8px;
    order: 2;
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
    order: 1;
}

.top-stories-content {
    flex: 1;
    overflow: hidden;
    position: relative;
    padding-left: 30px;
    display: flex;
    align-items: center;
}

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
    <!-- ЛЕВАЯ КОЛОНКА: Сообщение 404 + Все новости -->
    <div class="home-main-column">
        <!-- Блок 1: Сообщение о 404 (вместо слайдера) -->
        <div class="home-slider-wrapper">
            <div class="error-404-block" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 5px; padding: 40px 20px; text-align: center;">
                <div style="font-size: 80px; font-weight: 900; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1; margin-bottom: 10px;">
                    404
                </div>
                <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 8px; color: #333;">
                    Материал не найден
                </h1>
                <p style="font-size: 15px; color: #666; margin: 0;">
                    Запрашиваемая страница не существует или была перемещена
                </p>
            </div>
        </div>
        
        <!-- Баннер между сообщением и новостями -->
        <div style="margin: 20px 0; text-align: center;">
            @banner('content-top')
        </div>
        
        <!-- Блок 2: Все новости -->
        <div class="home-all-news">
            <h2 style="font-size: 24px; margin-top: 0; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 3px solid #c80000; color: #222;">
                Все новости
            </h2>
            
            @if(isset($posts) && $posts->count() > 0)
            <div class="posts-grid" id="posts-container">
                @foreach($posts as $post)
                    @include('partials.post-card', ['post' => $post])
                @endforeach
            </div>
            @else
            <div style="text-align: center; padding: 40px 0;">
                <p style="color: #666; font-size: 16px; margin-bottom: 20px;">Пока нет новостей для отображения</p>
                <a href="{{ url('/') }}" style="display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 5px; font-weight: 600;">
                    Перейти на главную
                </a>
            </div>
            @endif
            
            <!-- Индикатор загрузки -->
            <div id="loading-indicator" style="display: none; text-align: center; padding: 40px 0;">
                <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #c80000; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p style="margin-top: 15px; color: #666; font-size: 14px;">Загружаем еще материалы...</p>
            </div>
            
            <!-- Триггер для автоматической подгрузки -->
            <div id="load-trigger" style="height: 1px;"></div>
        </div>
    </div>
    
    <!-- ПРАВАЯ КОЛОНКА: Единый сайдбар (Интервью, Баннер, Релизы, Популярное, Календарь и т.д.) -->
    <aside class="home-sidebar-column">
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
                        $thumbnail = \App\Helpers\ContentHelper::getFeaturedImage($interview);
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
                        $thumbnail = \App\Helpers\ContentHelper::getFeaturedImage($release);
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
    grid-template-columns: 3fr 1fr;
    gap: 30px;
    align-items: start;
}

.home-main-column {
    min-width: 0;
}

.home-slider-wrapper {
    margin-bottom: 20px;
}

.home-all-news {
    margin-top: 0;
}

.posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.home-sidebar-column {
    position: sticky;
    top: 20px;
    align-self: start;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Адаптивность */
@media (max-width: 1200px) {
    .home-page-grid {
        grid-template-columns: 2.5fr 1fr;
        gap: 25px;
    }
}

@media (max-width: 992px) {
    .home-page-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .home-sidebar-column {
        position: static;
    }
    
    .posts-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}

@media (max-width: 768px) {
    .posts-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .error-404-block {
        padding: 30px 15px !important;
    }
    
    .error-404-block div[style*="font-size: 80px"] {
        font-size: 60px !important;
    }
    
    .error-404-block h1 {
        font-size: 22px !important;
    }
    
    .error-404-block p {
        font-size: 14px !important;
    }
}
</style>

@if(isset($posts) && $posts->count() > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
    let offset = {{ $posts->count() }}; // Начинаем с количества уже загруженных постов
    const totalPosts = {{ $totalPosts ?? 0 }};
    let isLoading = false;
    
    console.log('🔄 Infinite scroll initialized');
    console.log('📊 Initial offset:', offset);
    console.log('📊 Total posts:', totalPosts);
    
    const postsContainer = document.getElementById('posts-container');
    const loadingIndicator = document.getElementById('loading-indicator');
    const loadTrigger = document.getElementById('load-trigger');
    
    if (!postsContainer || !loadingIndicator || !loadTrigger) {
        console.error('❌ Required elements not found:', {
            postsContainer: !!postsContainer,
            loadingIndicator: !!loadingIndicator,
            loadTrigger: !!loadTrigger
        });
        return;
    }
    
    console.log('✅ All required elements found');
    
    async function loadMorePosts() {
        if (isLoading) {
            console.log('⏸️ Already loading, skipping...');
            return;
        }
        
        if (offset >= totalPosts) {
            console.log('🏁 All posts loaded');
            loadTrigger.style.display = 'none';
            return;
        }
        
        console.log('📥 Loading more posts, offset:', offset);
        isLoading = true;
        loadingIndicator.style.display = 'block';
        
        try {
            const url = `/api/load-more-posts?offset=${offset}&limit=12`;
            console.log('🌐 Fetching:', url);
            
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('📦 Received data:', data);
            
            if (data.html) {
                postsContainer.insertAdjacentHTML('beforeend', data.html);
                offset += 12;
                console.log('✅ Posts added, new offset:', offset);
                
                if (!data.hasMore) {
                    console.log('🏁 No more posts available');
                    loadTrigger.style.display = 'none';
                }
            } else {
                console.warn('⚠️ No HTML in response');
            }
        } catch (error) {
            console.error('❌ Error loading posts:', error);
        } finally {
            isLoading = false;
            loadingIndicator.style.display = 'none';
        }
    }
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            console.log('👁️ Trigger visibility:', entry.isIntersecting);
            if (entry.isIntersecting && !isLoading && offset < totalPosts) {
                console.log('🎯 Trigger hit! Loading posts...');
                loadMorePosts();
            }
        });
    }, {
        rootMargin: '200px'
    });
    
    if (offset < totalPosts) {
        console.log('👀 Starting to observe load trigger');
        observer.observe(loadTrigger);
    } else {
        console.log('🏁 All posts already loaded, hiding trigger');
        loadTrigger.style.display = 'none';
    }
});
</script>
@endif
@endsection
