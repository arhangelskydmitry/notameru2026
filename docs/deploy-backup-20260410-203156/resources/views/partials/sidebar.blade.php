{{-- Вертикальный рекламный баннер --}}
<div class="sidebar-widget sidebar-banner">
    @banner('sidebar-top')
</div>

{{-- Топ-5 популярных статей за неделю --}}
<div class="sidebar-widget popular-posts">
    <h3 class="widget-title">Популярное за неделю</h3>
    <div class="widget-content">
        @php
            $popularPosts = \App\Models\PostView::getTopPosts('week', 5);
        @endphp
        
        @foreach($popularPosts as $item)
            @php
                $post = $item->post;
                if (!$post) continue;
                
                // Получаем миниатюру
                $thumbnail = \App\Helpers\ContentHelper::getFeaturedImage($post);
            @endphp
            
            <div class="popular-post-item">
                @if($thumbnail && !str_contains($thumbnail, 'placeholder'))
                    <a href="{{ route('post', $post->post_name) }}" class="popular-post-image">
                        <img src="{{ $thumbnail }}" 
                             alt="{{ $post->post_title }}"
                             loading="lazy"
                             width="80"
                             height="80">
                    </a>
                @endif
                <div class="popular-post-content">
                    <h4><a href="{{ route('post', $post->post_name) }}">{{ Str::limit($post->post_title, 60) }}</a></h4>
                    <div class="popular-post-meta">{{ $post->post_date->format('d.m.Y') }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Интерактивный календарь --}}
<div class="sidebar-widget calendar-widget">
    <h3 class="widget-title">Календарь публикаций</h3>
    <div class="widget-content">
        @php
            $now = \Carbon\Carbon::now();
            $currentMonth = $now->month;
            $currentYear = $now->year;
            $daysInMonth = $now->daysInMonth;
            $firstDayOfMonth = \Carbon\Carbon::create($currentYear, $currentMonth, 1);
            $startDayOfWeek = $firstDayOfMonth->dayOfWeek; // 0 = воскресенье
            
            // Получаем даты с постами за текущий месяц
            $datesWithPosts = \App\Models\WordPress\Post::where('post_type', 'post')
                ->where('post_status', 'publish')
                ->whereYear('post_date', $currentYear)
                ->whereMonth('post_date', $currentMonth)
                ->selectRaw('DATE(post_date) as post_date, COUNT(*) as posts_count')
                ->groupBy('post_date')
                ->pluck('posts_count', 'post_date')
                ->toArray();
        @endphp
        
        <div class="calendar-header">
            <span class="calendar-month">{{ $now->locale('ru')->isoFormat('MMMM YYYY') }}</span>
        </div>
        
        <div class="calendar-grid">
            {{-- Названия дней недели --}}
            <div class="calendar-day-name">Пн</div>
            <div class="calendar-day-name">Вт</div>
            <div class="calendar-day-name">Ср</div>
            <div class="calendar-day-name">Чт</div>
            <div class="calendar-day-name">Пт</div>
            <div class="calendar-day-name">Сб</div>
            <div class="calendar-day-name">Вс</div>
            
            {{-- Пустые ячейки до первого дня месяца --}}
            @php
                // В Carbon воскресенье = 0, понедельник = 1, но нам нужно понедельник = 0
                $startOffset = ($startDayOfWeek === 0) ? 6 : $startDayOfWeek - 1;
            @endphp
            @for($i = 0; $i < $startOffset; $i++)
                <div class="calendar-day empty"></div>
            @endfor
            
            {{-- Дни месяца --}}
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
</div>

{{-- Облако популярных тегов --}}
<div class="sidebar-widget tags-cloud-widget">
    <h3 class="widget-title">Популярные теги</h3>
    <div class="widget-content">
        @php
            // Получаем топ-30 популярных тегов
            $popularTags = \App\Models\WordPress\TermTaxonomy::where('taxonomy', 'post_tag')
                ->where('count', '>', 0)
                ->with('term')
                ->orderBy('count', 'desc')
                ->limit(30)
                ->get();
            
            // Находим минимальное и максимальное значение для нормализации размеров
            $maxCount = $popularTags->max('count') ?: 1;
            $minCount = $popularTags->min('count') ?: 1;
        @endphp
        
        @if($popularTags->isNotEmpty())
            <div class="tags-cloud">
                @foreach($popularTags as $tagTaxonomy)
                    @php
                        // Вычисляем размер тега (от 0.8 до 2.0)
                        $ratio = ($tagTaxonomy->count - $minCount) / ($maxCount - $minCount);
                        $size = 0.8 + ($ratio * 1.2);
                        
                        // Случайный цвет из палитры
                        $colors = ['#c80000', '#e74c3c', '#3498db', '#9b59b6', '#1abc9c', '#f39c12', '#34495e', '#e67e22'];
                        $color = $colors[($tagTaxonomy->term_taxonomy_id % count($colors))];
                        
                        // Склонение слова "статья"
                        $count = $tagTaxonomy->count;
                        $countMod100 = $count % 100;
                        $countMod10 = $count % 10;
                        
                        if ($countMod100 > 10 && $countMod100 < 20) {
                            $word = 'статей';
                        } elseif ($countMod10 > 1 && $countMod10 < 5) {
                            $word = 'статьи';
                        } elseif ($countMod10 == 1) {
                            $word = 'статья';
                        } else {
                            $word = 'статей';
                        }
                    @endphp
                    
                    <a href="{{ route('tag', $tagTaxonomy->term->slug) }}" 
                       class="tag-cloud-item"
                       style="font-size: {{ $size }}em; color: {{ $color }};"
                       title="{{ $tagTaxonomy->term->name }} ({{ $tagTaxonomy->count }} {{ $word }})">
                        {{ $tagTaxonomy->term->name }}
                    </a>
                @endforeach
            </div>
        @else
            <p style="color: #999; text-align: center; padding: 20px 0;">Теги не найдены</p>
        @endif
    </div>
</div>

{{-- Блок с социальными сетями --}}
<div class="sidebar-widget social-links">
    <h3 class="widget-title">Присоединяйтесь к нам в социальных сетях!</h3>
    <div class="widget-content">
        <div class="social-icons-grid">
            <a href="https://vk.com/notame_ru" target="_blank" rel="noopener" class="social-icon vk" title="ВКонтакте">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M15.07 2H8.93C3.33 2 2 3.33 2 8.93v6.14C2 20.67 3.33 22 8.93 22h6.14c5.6 0 6.93-1.33 6.93-6.93V8.93C22 3.33 20.67 2 15.07 2zm3.18 14.53h-1.34c-.53 0-.69-.42-1.65-1.39-.83-.81-1.2-.92-1.41-.92-.29 0-.37.08-.37.47v1.27c0 .34-.11.55-1.01.55-1.5 0-3.16-.91-4.33-2.6-1.77-2.43-2.25-4.26-2.25-4.63 0-.21.08-.4.47-.4h1.34c.35 0 .48.16.62.54.71 2.05 1.88 3.85 2.37 3.85.18 0 .27-.09.27-.55v-2.15c-.06-.96-.56-1.04-.56-1.38 0-.17.14-.34.36-.34h2.1c.3 0 .41.16.41.5v2.89c0 .3.13.41.22.41.18 0 .35-.11.7-.46 1.08-1.21 1.85-3.07 1.85-3.07.1-.21.27-.4.62-.4h1.34c.4 0 .49.21.4.5-.16.76-1.9 3.17-1.9 3.17-.15.25-.21.36 0 .65.15.21.64.63 1 1.01.65.71 1.14 1.31 1.27 1.73.14.4-.07.61-.47.61z"/>
                </svg>
                <span>ВКонтакте</span>
            </a>
            
            <a href="https://dzen.ru/notameru" target="_blank" rel="noopener" class="social-icon dzen" title="Яндекс Дзен">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                </svg>
                <span>Яндекс Дзен</span>
            </a>
            
            <a href="https://t.me/notameru" target="_blank" rel="noopener" class="social-icon telegram" title="Telegram">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.2-.08-.06-.19-.04-.27-.02-.12.02-1.96 1.25-5.54 3.67-.52.36-1 .53-1.42.52-.47-.01-1.37-.26-2.03-.48-.82-.27-1.47-.42-1.42-.88.03-.24.37-.49 1.02-.75 4-1.74 6.68-2.89 8.03-3.45 3.82-1.59 4.62-1.87 5.14-1.87.11 0 .37.03.53.16.14.11.18.26.2.38.01.08.03.32.01.5z"/>
                </svg>
                <span>Telegram</span>
            </a>
            
            <a href="https://www.facebook.com/groups/notameru" target="_blank" rel="noopener" class="social-icon facebook" title="Facebook">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <span>Facebook</span>
            </a>
            
            <a href="https://www.instagram.com/notameru/" target="_blank" rel="noopener" class="social-icon instagram" title="Instagram">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
                <span>Instagram</span>
            </a>
            
            <a href="https://www.youtube.com/@notameru" target="_blank" rel="noopener" class="social-icon youtube" title="YouTube">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
                <span>YouTube</span>
            </a>
            
            <a href="https://ok.ru/notameru" target="_blank" rel="noopener" class="social-icon odnoklassniki" title="Одноклассники">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.6 0 12 0zm0 6c1.7 0 3 1.3 3 3s-1.3 3-3 3-3-1.3-3-3 1.3-3 3-3zm0 12c-2.2 0-4.2-.9-5.7-2.3l1.4-1.4c1.1 1.1 2.6 1.7 4.2 1.7s3.1-.6 4.2-1.7l1.4 1.4C16.2 17.1 14.2 18 12 18z"/>
                </svg>
                <span>Одноклассники</span>
            </a>
        </div>
    </div>
</div>

{{-- Блок счетчиков (Яндекс Метрика, Google Analytics и т.д.) --}}
@php
    $counters = \App\Models\Counter::getActiveForPosition('sidebar');
@endphp

@if($counters->isNotEmpty())
<div class="sidebar-widget counters-widget">
    <h3 class="widget-title">Статистика</h3>
    <div class="widget-content">
        @foreach($counters as $counter)
            <div class="counter-item" style="margin-bottom: 15px;">
                {!! $counter->code !!}
            </div>
        @endforeach
    </div>
</div>
@endif

<style>
/* Общие стили для виджетов сайдбара */
.sidebar-widget {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.widget-title {
    background: #c80000;
    color: #fff;
    padding: 12px 20px;
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    text-transform: uppercase;
}

.widget-content {
    padding: 15px;
}

/* Рекламный баннер */
.sidebar-banner {
    text-align: center;
    padding: 15px; /* Добавляем внутренние отступы как у widget-content */
}

.sidebar-banner img {
    max-width: 100%;
    height: auto;
    display: block;
}

/* Популярные посты */
.popular-post-item {
    display: flex;
    gap: 12px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.popular-post-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.popular-post-image {
    width: 80px;
    height: 80px;
    flex-shrink: 0;
    border-radius: 4px;
    overflow: hidden;
}

.popular-post-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.popular-post-image:hover img {
    transform: scale(1.05);
}

.popular-post-content {
    flex: 1;
    min-width: 0;
}

.popular-post-content h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    line-height: 1.4;
}

.popular-post-content h4 a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.popular-post-content h4 a:hover {
    color: #c80000;
}

.popular-post-meta {
    font-size: 12px;
    color: #999;
}

/* Социальные сети */
.social-icons-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.social-icon {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    border-radius: 6px;
    text-decoration: none;
    color: #fff;
    font-weight: 500;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.social-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.social-icon.vk {
    background: #0077FF;
}

.social-icon.dzen {
    background: #000000;
}

.social-icon.telegram {
    background: #0088cc;
}

.social-icon.facebook {
    background: #1877F2;
}

.social-icon.instagram {
    background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
}

.social-icon.youtube {
    background: #FF0000;
}

.social-icon.odnoklassniki {
    background: #EE8208;
}

.social-icon svg {
    flex-shrink: 0;
}

/* Счетчики */
.counters-widget .counter-item {
    text-align: center;
}

.counters-widget .counter-item img {
    max-width: 100%;
    height: auto;
}

/* ============================================
   ОБЛАКО ТЕГОВ
   ============================================ */
.tags-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    justify-content: flex-start;
    line-height: 1.8;
}

.tag-cloud-item {
    display: inline-block;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    padding: 4px 8px;
    border-radius: 4px;
    position: relative;
}

.tag-cloud-item:hover {
    transform: scale(1.15) translateY(-2px);
    text-shadow: 0 2px 8px rgba(0,0,0,0.2);
    background: rgba(200, 0, 0, 0.05);
}

.tag-cloud-item:active {
    transform: scale(1.05);
}

/* VK Виджет - УДАЛЕН */
/* Виджет ВКонтакте больше не используется */

/* ============================================
   КАЛЕНДАРЬ ПУБЛИКАЦИЙ - СОВРЕМЕННЫЙ ДИЗАЙН
   ============================================ */
.calendar-widget .widget-content {
    padding: 15px;
}

.calendar-header {
    text-align: center;
    margin-bottom: 15px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
}

.calendar-month {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    text-transform: capitalize;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 6px;
}

.calendar-day-name {
    text-align: center;
    font-size: 11px;
    font-weight: 600;
    color: #999;
    padding: 8px 0;
    text-transform: uppercase;
}

.calendar-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #f8f9fa;
    position: relative;
    cursor: default;
    transition: all 0.3s ease;
    text-decoration: none;
    color: #2c3e50;
}

.calendar-day.empty {
    background: transparent;
}

.calendar-day .day-number {
    font-size: 13px;
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
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 6px 20px rgba(200, 0, 0, 0.4);
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
    gap: 15px;
    margin-top: 15px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
    font-size: 11px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.legend-indicator {
    width: 16px;
    height: 16px;
    border-radius: 4px;
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

/* Адаптивность календаря */
@media (max-width: 768px) {
    .calendar-grid {
        gap: 4px;
    }
    
    .calendar-day .day-number {
        font-size: 12px;
    }
}

/* ============================================
   КОНЕЦ СТИЛЕЙ КАЛЕНДАРЯ
   ============================================ */

@media (max-width: 1024px) {
    .sidebar-widget {
        margin-bottom: 30px;
    }
    
    .social-icons-grid {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .social-icon {
        flex: 1;
        min-width: 150px;
        justify-content: center;
    }
}
</style>

