@extends('frontend.layout')

@php
    $seoService = app(\App\Services\SeoService::class);
    $seo = $seoService->getPageSeo($post);
@endphp

@section('title', $seo['title'])
@section('description', $seo['description'])
@section('keywords', $seo['keywords'])
@section('canonical', $seo['canonical'])
@section('robots', $seo['robots'])

{{-- Open Graph --}}
@section('og_type', $seo['og']['type'])
@section('og_title', $seo['og']['title'])
@section('og_description', $seo['og']['description'])
@section('og_url', $seo['og']['url'])
@section('og_image', $seo['og']['image'] ?? '')

{{-- Twitter Card --}}
@section('twitter_card', $seo['twitter']['card'])
@section('twitter_title', $seo['twitter']['title'])
@section('twitter_description', $seo['twitter']['description'])
@section('twitter_image', $seo['twitter']['image'] ?? '')

@section('breadcrumbs')
    <a href="{{ route('home') }}">Главная</a>
    <span class="separator">›</span>
    @if($post->categories->isNotEmpty())
        <a href="{{ route('category', $post->categories->first()->term->slug) }}">
            {{ $post->categories->first()->term->name }}
        </a>
        <span class="separator">›</span>
    @endif
    <span class="current">{{ Str::limit($post->post_title, 50) }}</span>
@endsection

{{-- Structured Data (Schema.org) --}}
@push('schema')
<script type="application/ld+json">
{!! json_encode($seo['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

{{-- Breadcrumbs Schema --}}
@push('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@@type": "ListItem",
      "position": 1,
      "name": "Главная",
      "item": "{{ route('home') }}"
    }
    @if($post->categories->isNotEmpty())
    ,{
      "@@type": "ListItem",
      "position": 2,
      "name": "{{ $post->categories->first()->term->name }}",
      "item": "{{ route('category', $post->categories->first()->term->slug) }}"
    }
    @endif
    ,{
      "@@type": "ListItem",
      "position": {{ $post->categories->isNotEmpty() ? 3 : 2 }},
      "name": "{{ $post->post_title }}",
      "item": "{{ $seo['canonical'] }}"
    }
  ]
}
</script>
@endpush

@section('content')
<div style="display: grid; grid-template-columns: 1fr 300px; gap: 40px;">
    <!-- Статья -->
    <article style="background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <header style="margin-bottom: 15px;">
            <h1 style="font-size: 36px; line-height: 1.2; margin-bottom: 10px; color: #2c3e50;">
                {{ $post->post_title }}
            </h1>
            
            @if($post->categories->isNotEmpty())
                <div class="categories" style="margin-top: 8px; margin-bottom: 10px;">
                    @foreach($post->categories as $category)
                        <a href="{{ route('category', $category->term->slug) }}" class="category-tag">
                            {{ $category->term->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </header>
        
        <div class="post-body" style="font-size: 18px; line-height: 1.8; color: #444;">
            {!! \App\Helpers\ContentHelper::getContent($post) !!}
        </div>
        
        <style>
        /* Стили для кликабельных изображений */
        .post-body img {
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        
        .post-body img:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        /* WordPress align классы */
        .post-body .aligncenter {
            display: block;
            margin: 20px auto;
        }
        
        .post-body .alignleft {
            float: left;
            margin: 10px 20px 20px 0;
        }
        
        .post-body .alignright {
            float: right;
            margin: 10px 0 20px 20px;
        }
        
        /* Ссылки на изображения */
        .post-body .post-image-link {
            display: inline-block;
            line-height: 0;
        }
        
        .post-body a.post-image-link.aligncenter {
            display: block;
            text-align: center;
            margin: 20px auto;
        }
        
        /* Модальное окно для изображения */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            animation: fadeIn 0.3s ease;
        }
        
        .image-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .image-modal img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            animation: zoomIn 0.3s ease;
        }
        
        .image-modal-close {
            position: absolute;
            top: 20px;
            right: 40px;
            font-size: 40px;
            color: white;
            cursor: pointer;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        
        .image-modal-close:hover {
            color: #c80000;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes zoomIn {
            from { transform: scale(0.8); }
            to { transform: scale(1); }
        }
        
        /* Стили для sidebar постов */
        .sidebar-post {
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        /* Стили для баннера в сайдбаре */
        .sidebar-banner {
            text-align: center;
            padding: 15px;
        }
        
        .sidebar-banner img {
            max-width: 100%;
            height: auto;
        }
        
        .sidebar-post:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .sidebar-post img {
            transition: transform 0.3s ease;
        }
        
        .sidebar-post a:hover img {
            transform: scale(1.05);
        }
        
        .sidebar-post h4 a {
            transition: color 0.3s ease;
        }
        
        .sidebar-post h4 a:hover {
            color: #c80000;
        }
        
        /* Календарь на странице поста */
        .calendar-sidebar h3 {
            margin-bottom: 15px;
        }
        
        .calendar-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .calendar-month {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            text-transform: capitalize;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }
        
        .calendar-day-name {
            text-align: center;
            font-size: 10px;
            font-weight: 600;
            color: #999;
            padding: 6px 0;
            text-transform: uppercase;
        }
        
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            background: #f8f9fa;
            position: relative;
            cursor: default;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #2c3e50;
            font-size: 12px;
        }
        
        .calendar-day.empty {
            background: transparent;
        }
        
        .calendar-day .day-number {
            font-weight: 500;
            z-index: 1;
        }
        
        .calendar-day.has-posts {
            background: linear-gradient(135deg, #c80000 0%, #ff4444 100%);
            color: white;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(200, 0, 0, 0.2);
        }
        
        .calendar-day.has-posts:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 16px rgba(200, 0, 0, 0.4);
        }
        
        .calendar-day.today {
            border: 2px solid #c80000;
            font-weight: 700;
        }
        
        .calendar-day.today:not(.has-posts) {
            background: #fff;
            color: #c80000;
        }
        
        .calendar-legend {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
            font-size: 10px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .legend-indicator {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            flex-shrink: 0;
        }
        
        .legend-indicator.has-posts-color {
            background: linear-gradient(135deg, #c80000 0%, #ff4444 100%);
        }
        
        .legend-indicator.today-color {
            background: #fff;
            border: 2px solid #c80000;
        }
        
        .legend-text {
            color: #666;
            font-weight: 500;
        }
        
        /* Адаптивные стили для календарей */
        /* По умолчанию мобильный календарь скрыт, сайдбарный виден */
        .mobile-calendar {
            display: none;
        }
        
        /* На мобильных устройствах показываем мобильный календарь, скрываем сайдбарный */
        @media (max-width: 768px) {
            /* ПОЛНАЯ МОБИЛЬНАЯ ОПТИМИЗАЦИЯ */
            
            /* Убираем горизонтальную прокрутку */
            body {
                overflow-x: hidden !important;
            }
            
            /* Контейнер статьи */
            div[style*="grid-template-columns: 1fr 300px"] {
                grid-template-columns: 1fr !important;
                gap: 0 !important;
                padding: 0 !important;
            }
            
            /* Сама статья - уменьшаем padding */
            article[style*="padding: 40px"] {
                padding: 15px !important;
            }
            
            /* Контент статьи */
            .post-body {
                font-size: 16px !important;
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
            }
            
            /* ВСЕ изображения */
            .post-body img,
            .post-body iframe,
            .post-body video,
            .post-body embed,
            .post-body object {
                max-width: 100% !important;
                height: auto !important;
                width: auto !important;
            }
            
            /* Таблицы */
            .post-body table {
                display: block !important;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
                max-width: 100% !important;
            }
            
            /* Pre и Code блоки */
            .post-body pre,
            .post-body code {
                max-width: 100% !important;
                overflow-x: auto !important;
                word-wrap: break-word !important;
                white-space: pre-wrap !important;
            }
            
            /* Встроенные элементы */
            .post-body iframe[src*="youtube"],
            .post-body iframe[src*="vimeo"],
            .post-body iframe[src*="rutube"] {
                width: 100% !important;
                height: auto !important;
                aspect-ratio: 16/9 !important;
            }
            
            .mobile-calendar {
                display: block;
                padding: 15px !important;
            }
            
            .calendar-sidebar {
                display: none !important;
            }
            
            /* Фиксируем сетку календаря */
            .mobile-calendar .calendar-grid {
                display: grid !important;
                grid-template-columns: repeat(7, 1fr) !important;
                gap: 3px !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Стили для ячеек календаря */
            .mobile-calendar .calendar-day,
            .mobile-calendar .calendar-day-name {
                font-size: 11px !important;
                padding: 8px 2px !important;
                min-height: 36px !important;
            }
            
            .mobile-calendar .calendar-day {
                aspect-ratio: 1 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 6px !important;
            }
            
            .mobile-calendar .calendar-day-name {
                font-size: 10px !important;
                padding: 6px 2px !important;
                min-height: auto !important;
            }
            
            /* Легенда календаря */
            .mobile-calendar .calendar-legend {
                flex-wrap: wrap !important;
                gap: 10px !important;
                justify-content: center !important;
            }
            
            .mobile-calendar .legend-item {
                font-size: 10px !important;
            }
            
            .mobile-calendar .legend-indicator {
                width: 14px !important;
                height: 14px !important;
            }
        }
        
        /* Для совсем маленьких экранов */
        @media (max-width: 480px) {
            .mobile-calendar {
                padding: 10px !important;
            }
            
            .mobile-calendar .calendar-grid {
                gap: 2px !important;
            }
            
            .mobile-calendar .calendar-day {
                font-size: 10px !important;
                padding: 6px 2px !important;
                min-height: 32px !important;
            }
            
            .mobile-calendar .calendar-day-name {
                font-size: 9px !important;
                padding: 4px 1px !important;
            }
        }
        
        /* Hover эффект для мобильного календаря */
        .mobile-calendar .calendar-day.has-posts:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 20px rgba(200, 0, 0, 0.4);
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Создаем модальное окно
            const modal = document.createElement('div');
            modal.className = 'image-modal';
            modal.innerHTML = '<span class="image-modal-close">&times;</span><img src="" alt="Просмотр изображения">';
            document.body.appendChild(modal);
            
            const modalImg = modal.querySelector('img');
            const closeBtn = modal.querySelector('.image-modal-close');
            
            // Находим все изображения в посте
            const postImages = document.querySelectorAll('.post-body img');
            
            postImages.forEach(img => {
                // Делаем изображение кликабельным
                img.style.cursor = 'pointer';
                
                img.addEventListener('click', function(e) {
                    e.preventDefault();
                    modal.classList.add('active');
                    modalImg.src = this.src;
                    document.body.style.overflow = 'hidden';
                });
            });
            
            // Закрытие модального окна
            closeBtn.addEventListener('click', function() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // Закрытие при клике вне изображения
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
            
            // Закрытие по Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
        </script>
        
        <div class="post-meta" style="display: flex; gap: 20px; padding-top: 20px; margin-top: 30px; border-top: 2px solid #eee; color: #666; font-size: 14px;">
            <span>📅 {{ $post->post_date->format('d.m.Y H:i') }}</span>
            @if($post->author)
                <span>✍️ <a href="{{ route('author', $post->author->ID) }}" style="color: inherit; text-decoration: none; font-weight: 500;">{{ $post->author->display_name }}</a></span>
            @endif
            <span>👁 {{ $post->getMeta('post_views_count', 0) }} просмотров</span>
        </div>
        
        @if($post->tags->isNotEmpty())
            <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #eee;">
                <strong>Теги:</strong>
                @foreach($post->tags as $tag)
                    <a href="{{ route('tag', $tag->term->slug) }}" 
                        style="display: inline-block; background: #ecf0f1; padding: 6px 12px; border-radius: 4px; margin: 5px; text-decoration: none; color: #333; font-size: 14px;">
                        #{{ $tag->term->name }}
                    </a>
                @endforeach
            </div>
        @endif
        
        @if(count($relatedPosts) > 0)
            <div style="margin-top: 40px;">
                <h3 style="font-size: 24px; margin-bottom: 20px;">Похожие статьи</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                    @foreach($relatedPosts as $related)
                        <div class="post-card" style="font-size: 14px;">
                            <div class="post-content">
                                <h4 style="font-size: 16px; margin-bottom: 8px;">
                                    <a href="{{ route('post', $related->post_name) }}" style="text-decoration: none; color: #333;">
                                        {{ Str::limit($related->post_title, 60) }}
                                    </a>
                                </h4>
                                <div style="font-size: 12px; color: #999;">
                                    {{ $related->post_date->format('d.m.Y') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        {{-- Календарь публикаций для мобильных --}}
        @php
            $now = \Carbon\Carbon::now();
            $currentMonth = $now->month;
            $currentYear = $now->year;
            $daysInMonth = $now->daysInMonth;
            $firstDayOfMonth = \Carbon\Carbon::create($currentYear, $currentMonth, 1);
            $startDayOfWeek = $firstDayOfMonth->dayOfWeek;
            
            $datesWithPosts = \App\Models\WordPress\Post::publiclyVisible()
                ->whereYear('post_date', $currentYear)
                ->whereMonth('post_date', $currentMonth)
                ->selectRaw('DATE(post_date) as post_date, COUNT(*) as posts_count')
                ->groupBy('post_date')
                ->pluck('posts_count', 'post_date')
                ->toArray();
        @endphp
        
        <div class="mobile-calendar" style="margin-top: 40px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="font-size: 20px; margin-bottom: 15px; color: #2c3e50; border-bottom: 2px solid #c80000; padding-bottom: 10px;">Календарь публикаций</h3>
            <div class="calendar-header" style="text-align: center; margin-bottom: 15px; padding-bottom: 12px; border-bottom: 2px solid #f0f0f0;">
                <span class="calendar-month" style="font-size: 16px; font-weight: 600; color: #2c3e50; text-transform: capitalize;">{{ $now->locale('ru')->isoFormat('MMMM YYYY') }}</span>
            </div>
            
            <div class="calendar-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px;">
                <div class="calendar-day-name" style="text-align: center; font-size: 11px; font-weight: 600; color: #999; padding: 8px 0; text-transform: uppercase;">Пн</div>
                <div class="calendar-day-name" style="text-align: center; font-size: 11px; font-weight: 600; color: #999; padding: 8px 0; text-transform: uppercase;">Вт</div>
                <div class="calendar-day-name" style="text-align: center; font-size: 11px; font-weight: 600; color: #999; padding: 8px 0; text-transform: uppercase;">Ср</div>
                <div class="calendar-day-name" style="text-align: center; font-size: 11px; font-weight: 600; color: #999; padding: 8px 0; text-transform: uppercase;">Чт</div>
                <div class="calendar-day-name" style="text-align: center; font-size: 11px; font-weight: 600; color: #999; padding: 8px 0; text-transform: uppercase;">Пт</div>
                <div class="calendar-day-name" style="text-align: center; font-size: 11px; font-weight: 600; color: #999; padding: 8px 0; text-transform: uppercase;">Сб</div>
                <div class="calendar-day-name" style="text-align: center; font-size: 11px; font-weight: 600; color: #999; padding: 8px 0; text-transform: uppercase;">Вс</div>
                
                @php
                    $startOffset = ($startDayOfWeek === 0) ? 6 : $startDayOfWeek - 1;
                @endphp
                @for($i = 0; $i < $startOffset; $i++)
                    <div class="calendar-day empty" style="aspect-ratio: 1; background: transparent;"></div>
                @endfor
                
                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $date = \Carbon\Carbon::create($currentYear, $currentMonth, $day);
                        $dateStr = $date->format('Y-m-d');
                        $hasPost = isset($datesWithPosts[$dateStr]);
                        $isToday = $date->isToday();
                    @endphp
                    
                    @if($hasPost)
                        <a href="{{ route('posts.by-date', $dateStr) }}" 
                           class="calendar-day {{ $isToday ? 'today' : '' }} has-posts"
                           title="Посмотреть публикации за этот день"
                           style="aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: linear-gradient(135deg, #c80000 0%, #ff4444 100%); color: white; cursor: pointer; box-shadow: 0 2px 8px rgba(200, 0, 0, 0.2); transition: all 0.3s ease; text-decoration: none; font-size: 13px; font-weight: 500; {{ $isToday ? 'border: 2px solid #c80000; font-weight: 700;' : '' }}">
                            {{ $day }}
                        </a>
                    @else
                        <div class="calendar-day {{ $isToday ? 'today' : '' }}"
                             style="aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #f8f9fa; color: #2c3e50; cursor: default; transition: all 0.3s ease; font-size: 13px; font-weight: 500; {{ $isToday ? 'border: 2px solid #c80000; background: #fff; color: #c80000; font-weight: 700;' : '' }}">
                            {{ $day }}
                        </div>
                    @endif
                @endfor
            </div>
            
            <div class="calendar-legend" style="display: flex; gap: 15px; margin-top: 15px; padding-top: 12px; border-top: 1px solid #f0f0f0; font-size: 11px;">
                <div class="legend-item" style="display: flex; align-items: center; gap: 6px;">
                    <span class="legend-indicator has-posts-color" style="width: 16px; height: 16px; border-radius: 4px; flex-shrink: 0; background: linear-gradient(135deg, #c80000 0%, #ff4444 100%);"></span>
                    <span class="legend-text" style="color: #666; font-weight: 500;">Есть посты</span>
                </div>
                <div class="legend-item" style="display: flex; align-items: center; gap: 6px;">
                    <span class="legend-indicator today-color" style="width: 16px; height: 16px; border-radius: 4px; flex-shrink: 0; background: #fff; border: 2px solid #c80000;"></span>
                    <span class="legend-text" style="color: #666; font-weight: 500;">Сегодня</span>
                </div>
            </div>
        </div>
    </article>
    
    <!-- Сайдбар -->
    <aside style="position: relative;">
        <div style="position: sticky; top: 80px;">
            {{-- Баннер sidebar-top --}}
            <div class="sidebar sidebar-banner" style="margin-bottom: 20px;">
                @banner('sidebar-top')
            </div>
            
            <div class="sidebar">
                <h3>Поделиться</h3>
                <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
                    <a href="https://vk.com/share.php?url={{ urlencode(route('post', $post->post_name)) }}" 
                        target="_blank"
                        style="background: #4680C2; color: white; padding: 10px; text-align: center; border-radius: 6px; text-decoration: none;">
                        VK
                    </a>
                    <a href="https://t.me/share/url?url={{ urlencode(route('post', $post->post_name)) }}" 
                        target="_blank"
                        style="background: #0088cc; color: white; padding: 10px; text-align: center; border-radius: 6px; text-decoration: none;">
                        Telegram
                    </a>
                </div>
            </div>
            
            {{-- Календарь публикаций --}}
            @php
                $now = \Carbon\Carbon::now();
                $currentMonth = $now->month;
                $currentYear = $now->year;
                $daysInMonth = $now->daysInMonth;
                $firstDayOfMonth = \Carbon\Carbon::create($currentYear, $currentMonth, 1);
                $startDayOfWeek = $firstDayOfMonth->dayOfWeek;
                
                $datesWithPosts = \App\Models\WordPress\Post::publiclyVisible()
                    ->whereYear('post_date', $currentYear)
                    ->whereMonth('post_date', $currentMonth)
                    ->selectRaw('DATE(post_date) as post_date, COUNT(*) as posts_count')
                    ->groupBy('post_date')
                    ->pluck('posts_count', 'post_date')
                    ->toArray();
            @endphp
            
            <div class="sidebar calendar-sidebar" style="margin-top: 20px;">
                <h3>Календарь публикаций</h3>
                <div class="calendar-header">
                    <span class="calendar-month">{{ $now->locale('ru')->isoFormat('MMMM YYYY') }}</span>
                </div>
                
                <div class="calendar-grid">
                    <div class="calendar-day-name">Пн</div>
                    <div class="calendar-day-name">Вт</div>
                    <div class="calendar-day-name">Ср</div>
                    <div class="calendar-day-name">Чт</div>
                    <div class="calendar-day-name">Пт</div>
                    <div class="calendar-day-name">Сб</div>
                    <div class="calendar-day-name">Вс</div>
                    
                    @php
                        $startOffset = ($startDayOfWeek === 0) ? 6 : $startDayOfWeek - 1;
                    @endphp
                    @for($i = 0; $i < $startOffset; $i++)
                        <div class="calendar-day empty"></div>
                    @endfor
                    
                    @for($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $date = \Carbon\Carbon::create($currentYear, $currentMonth, $day);
                            $dateStr = $date->format('Y-m-d');
                            $hasPost = isset($datesWithPosts[$dateStr]);
                            $isToday = $date->isToday();
                        @endphp
                        
                        @if($hasPost)
                            <a href="{{ route('posts.by-date', $dateStr) }}" 
                               class="calendar-day {{ $isToday ? 'today' : '' }} has-posts"
                               title="Посмотреть публикации за этот день">
                                <span class="day-number">{{ $day }}</span>
                            </a>
                        @else
                            <div class="calendar-day {{ $isToday ? 'today' : '' }}">
                                <span class="day-number">{{ $day }}</span>
                            </div>
                        @endif
                    @endfor
                </div>
                
                <div class="calendar-legend">
                    <div class="legend-item">
                        <span class="legend-indicator has-posts-color"></span>
                        <span class="legend-text">Есть посты</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-indicator today-color"></span>
                        <span class="legend-text">Сегодня</span>
                    </div>
                </div>
            </div>
            
            <div class="sidebar" style="margin-top: 20px;">
                <h3>Категории</h3>
                <ul>
                    @foreach($post->categories as $category)
                        <li>
                            <a href="{{ route('category', $category->term->slug) }}">
                                {{ $category->term->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            
            <!-- Популярное -->
            @php
                $popularPosts = \App\Models\WordPress\Post::publiclyVisible()
                    ->where('ID', '!=', $post->ID)
                    ->orderByDesc(function($query) {
                        $query->selectRaw('CAST(meta_value AS UNSIGNED)')
                            ->from('wp_postmeta')
                            ->whereColumn('wp_postmeta.post_id', 'wp_posts.ID')
                            ->where('meta_key', 'post_views_count');
                    })
                    ->limit(5)
                    ->get();
            @endphp
            
            @if($popularPosts->isNotEmpty())
                <div class="sidebar" style="margin-top: 20px;">
                    <h3>Популярное</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 15px;">
                        @foreach($popularPosts as $popular)
                            <div class="sidebar-post">
                                @php
                                    $thumbnailId = $popular->getMeta('_thumbnail_id');
                                    $thumbnail = null;
                                    if ($thumbnailId) {
                                        $attachment = \App\Models\WordPress\Post::find($thumbnailId);
                                        if ($attachment) {
                                            $thumbnail = str_replace('http://notame.ru/wp-content/uploads', '/imgnews', $attachment->guid);
                                            $thumbnail = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $thumbnail);
                                        }
                                    }
                                @endphp
                                
                                @if($thumbnail && file_exists(public_path($thumbnail)))
                                    <a href="{{ route('post', $popular->post_name) }}" style="display: block; margin-bottom: 8px;">
                                        <img src="{{ $thumbnail }}" 
                                             alt="{{ $popular->post_title }}"
                                             style="width: 100%; aspect-ratio: 16 / 9; height: auto; object-fit: cover; background: #f4f4f4; border-radius: 6px;">
                                    </a>
                                @endif
                                
                                <h4 style="font-size: 14px; line-height: 1.4; margin: 0;">
                                    <a href="{{ route('post', $popular->post_name) }}" 
                                       style="text-decoration: none; color: #333;">
                                        {{ Str::limit($popular->post_title, 60) }}
                                    </a>
                                </h4>
                                
                                <div style="font-size: 12px; color: #999; margin-top: 5px;">
                                    👁 {{ $popular->getMeta('post_views_count', 0) }} просмотров
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </aside>
</div>
@endsection
