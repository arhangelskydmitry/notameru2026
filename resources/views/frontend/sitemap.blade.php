@extends('frontend.layout')

@section('title', 'Карта сайта - ' . config('app.name'))
@section('description', 'HTML-карта сайта «Нота Миру» со всеми статьями, рубриками и архивами по годам, месяцам и дням публикации для удобной навигации пользователей и поисковых систем.')

@section('content')
<div style="max-width: 1400px; margin: 0 auto; padding: 40px 20px;">
    <h1 style="font-size: 36px; margin-bottom: 20px; color: #2c3e50;">
        Карта сайта
    </h1>
    
    <p style="color: #666; margin-bottom: 30px; font-size: 16px;">
        Все статьи сайта, организованные по годам, месяцам и дням публикации
    </p>
    
    <!-- Контейнер с двумя колонками -->
    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 40px;">
        <!-- Основной контент - карта сайта -->
        <div>
            <div id="sitemap-container" style="background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                @foreach($years as $year)
                <div class="year-block" style="margin-bottom: 30px; border-left: 4px solid #c80000; padding-left: 20px;">
                    <div class="year-header" style="margin-bottom: 15px;">
                        <h2 style="margin: 0; display: inline-flex; align-items: center; gap: 10px;">
                            <button class="toggle-year" 
                                    data-year="{{ $year->year }}" 
                                    style="background: none; border: none; cursor: pointer; font-size: 24px; color: #c80000; padding: 0; line-height: 1;">
                                <span class="toggle-icon">▶</span>
                            </button>
                            <a href="{{ route('posts.by-year', $year->year) }}" 
                               style="color: #2c3e50; text-decoration: none; font-size: 28px; font-weight: 700;"
                               onmouseover="this.style.color='#c80000'"
                               onmouseout="this.style.color='#2c3e50'">
                                {{ $year->year }} год
                            </a>
                            <span style="background: #c80000; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: normal;">
                                {{ $year->count }} {{ \App\Helpers\ContentHelper::pluralize($year->count, ['статья', 'статьи', 'статей']) }}
                            </span>
                        </h2>
                    </div>
                    
                    <div class="months-container" 
                         id="months-{{ $year->year }}" 
                         style="display: none; padding-left: 30px; margin-left: 20px; border-left: 2px solid #e0e0e0;">
                        <div class="loading-spinner" style="text-align: center; padding: 20px; color: #999;">
                            <div style="display: inline-block; width: 30px; height: 30px; border: 3px solid #f3f3f3; border-top: 3px solid #c80000; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                            <p style="margin-top: 10px; font-size: 14px;">Загрузка месяцев...</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        
        <!-- Sidebar справа -->
        <aside style="position: sticky; top: 20px; align-self: start;">
            @include('partials.sidebar')
        </aside>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Адаптивность для карты сайта */
@media (max-width: 1024px) {
    div[style*="grid-template-columns: 1fr 300px"] {
        grid-template-columns: 1fr !important;
    }
    
    aside[style*="position: sticky"] {
        position: static !important;
        margin-top: 40px;
    }
}

@media (max-width: 768px) {
    div[style*="max-width: 1400px"] {
        padding: 20px 15px !important;
    }
    
    h1[style*="font-size: 36px"] {
        font-size: 24px !important;
    }
    
    /* Оптимизация блока карты сайта */
    #sitemap-container {
        padding: 15px !important;
    }
    
    /* Блок года */
    .year-block {
        padding-left: 10px !important;
        margin-bottom: 20px !important;
    }
    
    .year-header h2 {
        flex-wrap: wrap !important;
        gap: 8px !important;
    }
    
    .year-header h2 a {
        font-size: 20px !important;
    }
    
    .year-header h2 span[style*="background"] {
        font-size: 12px !important;
        padding: 3px 10px !important;
    }
    
    /* Контейнер месяцев */
    .months-container {
        padding-left: 10px !important;
        margin-left: 5px !important;
    }
    
    /* Блок месяца */
    .month-block {
        padding: 10px !important;
        margin-bottom: 15px !important;
    }
    
    .month-block > div[style*="display: flex"] {
        flex-wrap: wrap !important;
        gap: 8px !important;
    }
    
    .month-block a[style*="font-size: 18px"] {
        font-size: 16px !important;
    }
    
    /* Контейнер дней */
    .days-container {
        padding-left: 10px !important;
    }
    
    /* Блок дня */
    .day-block {
        padding: 8px 10px !important;
        margin: 8px 0 !important;
    }
    
    .day-block > div[style*="display: flex"] {
        flex-wrap: wrap !important;
        gap: 6px !important;
    }
    
    .day-block a[style*="font-size: 16px"] {
        font-size: 14px !important;
    }
    
    /* Контейнер постов */
    .posts-container {
        padding-left: 5px !important;
    }
    
    /* Элемент поста */
    .post-item {
        padding: 6px 10px !important;
        margin: 4px 0 !important;
    }
    
    .post-item a {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 4px !important;
        font-size: 14px !important;
    }
    
    .post-time {
        font-size: 11px !important;
    }
    
    /* Кнопки раскрытия */
    .toggle-year,
    .toggle-month,
    .toggle-day {
        font-size: 16px !important;
        padding: 4px !important;
        min-width: 24px !important;
    }
    
    /* Бейджи с количеством */
    .count-badge {
        font-size: 11px !important;
        padding: 2px 6px !important;
    }
}

@media (max-width: 480px) {
    h1[style*="font-size: 36px"] {
        font-size: 20px !important;
    }
    
    #sitemap-container {
        padding: 10px !important;
    }
    
    .year-header h2 a {
        font-size: 18px !important;
    }
    
    .month-block a[style*="font-size: 18px"] {
        font-size: 14px !important;
    }
    
    .day-block a[style*="font-size: 16px"] {
        font-size: 13px !important;
    }
    
    .post-item a {
        font-size: 13px !important;
    }
}

.year-block {
    transition: all 0.3s ease;
}

.year-header {
    transition: all 0.3s ease;
}

.toggle-year {
    transition: transform 0.3s ease;
}

.toggle-year.active .toggle-icon {
    display: inline-block;
    transform: rotate(90deg);
}

.month-block {
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
    transition: background 0.3s ease;
}

.month-block:hover {
    background: #f0f0f0;
}

.day-block {
    margin: 10px 0;
    padding: 10px 15px;
    background: #fff;
    border-radius: 6px;
    border-left: 3px solid #e0e0e0;
    transition: all 0.3s ease;
}

.day-block:hover {
    border-left-color: #c80000;
    background: #fafafa;
}

.post-item {
    padding: 8px 15px;
    margin: 5px 0;
    background: #fff;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.post-item:hover {
    background: #f5f5f5;
    padding-left: 20px;
}

.post-item a {
    color: #444;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
}

.post-item a:hover {
    color: #c80000;
}

.post-time {
    font-size: 12px;
    color: #999;
    min-width: 45px;
}

.count-badge {
    background: #e0e0e0;
    color: #666;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loadedMonths = new Set();
    const loadedDays = new Set();
    const loadedPosts = new Set();
    
    // Обработчик клика на год
    document.querySelectorAll('.toggle-year').forEach(button => {
        button.addEventListener('click', function() {
            const year = this.dataset.year;
            const monthsContainer = document.getElementById(`months-${year}`);
            const isVisible = monthsContainer.style.display !== 'none';
            
            if (isVisible) {
                // Скрываем
                monthsContainer.style.display = 'none';
                this.classList.remove('active');
            } else {
                // Показываем
                monthsContainer.style.display = 'block';
                this.classList.add('active');
                
                // Загружаем месяцы, если еще не загружены
                if (!loadedMonths.has(year)) {
                    loadMonths(year);
                    loadedMonths.add(year);
                }
            }
        });
    });
    
    // Загрузка месяцев
    async function loadMonths(year) {
        const container = document.getElementById(`months-${year}`);
        
        try {
            const response = await fetch(`/api/sitemap/months/${year}`);
            const months = await response.json();
            
            if (months.length === 0) {
                container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Нет статей за этот год</p>';
                return;
            }
            
            let html = '';
            months.forEach(month => {
                html += `
                    <div class="month-block">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <button class="toggle-month" 
                                    data-year="${year}" 
                                    data-month="${String(month.month).padStart(2, '0')}"
                                    style="background: none; border: none; cursor: pointer; font-size: 18px; color: #c80000; padding: 0; line-height: 1;">
                                <span class="toggle-icon">▶</span>
                            </button>
                            <a href="${month.url}" 
                               style="color: #444; text-decoration: none; font-size: 18px; font-weight: 600;"
                               onmouseover="this.style.color='#c80000'"
                               onmouseout="this.style.color='#444'">
                                ${month.month_name}
                            </a>
                            <span class="count-badge">${month.count}</span>
                        </div>
                        <div class="days-container" 
                             id="days-${year}-${String(month.month).padStart(2, '0')}" 
                             style="display: none; padding-left: 20px;">
                            <div class="loading-spinner" style="text-align: center; padding: 15px; color: #999;">
                                <div style="display: inline-block; width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid #c80000; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            
            // Добавляем обработчики для месяцев
            container.querySelectorAll('.toggle-month').forEach(button => {
                button.addEventListener('click', function() {
                    const year = this.dataset.year;
                    const month = this.dataset.month;
                    const key = `${year}-${month}`;
                    const daysContainer = document.getElementById(`days-${key}`);
                    const isVisible = daysContainer.style.display !== 'none';
                    
                    if (isVisible) {
                        daysContainer.style.display = 'none';
                        this.classList.remove('active');
                    } else {
                        daysContainer.style.display = 'block';
                        this.classList.add('active');
                        
                        if (!loadedDays.has(key)) {
                            loadDays(year, month);
                            loadedDays.add(key);
                        }
                    }
                });
            });
            
        } catch (error) {
            container.innerHTML = '<p style="color: #c80000; text-align: center; padding: 20px;">Ошибка загрузки данных</p>';
            console.error('Error loading months:', error);
        }
    }
    
    // Загрузка дней
    async function loadDays(year, month) {
        const container = document.getElementById(`days-${year}-${month}`);
        
        try {
            const response = await fetch(`/api/sitemap/days/${year}/${month}`);
            const days = await response.json();
            
            if (days.length === 0) {
                container.innerHTML = '<p style="color: #999; padding: 10px;">Нет статей за этот месяц</p>';
                return;
            }
            
            // Названия месяцев
            const monthNames = ['', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 
                                'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
            
            let html = '';
            days.forEach(day => {
                const monthNum = parseInt(month);
                const dayFormatted = `${day.day} ${monthNames[monthNum]}`;
                
                html += `
                    <div class="day-block">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <button class="toggle-day" 
                                    data-year="${year}" 
                                    data-month="${month}" 
                                    data-day="${String(day.day).padStart(2, '0')}"
                                    style="background: none; border: none; cursor: pointer; font-size: 14px; color: #c80000; padding: 0; line-height: 1;">
                                <span class="toggle-icon">▶</span>
                            </button>
                            <a href="${day.url}" 
                               style="color: #444; text-decoration: none; font-size: 16px; font-weight: 500;"
                               onmouseover="this.style.color='#c80000'"
                               onmouseout="this.style.color='#444'">
                                ${dayFormatted}
                            </a>
                            <span class="count-badge">${day.count}</span>
                        </div>
                        <div class="posts-container" 
                             id="posts-${year}-${month}-${String(day.day).padStart(2, '0')}" 
                             style="display: none; padding-left: 20px;">
                            <div class="loading-spinner" style="text-align: center; padding: 10px; color: #999;">
                                <div style="display: inline-block; width: 16px; height: 16px; border: 2px solid #f3f3f3; border-top: 2px solid #c80000; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            
            // Добавляем обработчики для дней
            container.querySelectorAll('.toggle-day').forEach(button => {
                button.addEventListener('click', function() {
                    const year = this.dataset.year;
                    const month = this.dataset.month;
                    const day = this.dataset.day;
                    const key = `${year}-${month}-${day}`;
                    const postsContainer = document.getElementById(`posts-${key}`);
                    const isVisible = postsContainer.style.display !== 'none';
                    
                    if (isVisible) {
                        postsContainer.style.display = 'none';
                        this.classList.remove('active');
                    } else {
                        postsContainer.style.display = 'block';
                        this.classList.add('active');
                        
                        if (!loadedPosts.has(key)) {
                            loadPosts(year, month, day);
                            loadedPosts.add(key);
                        }
                    }
                });
            });
            
        } catch (error) {
            container.innerHTML = '<p style="color: #c80000; padding: 10px;">Ошибка загрузки данных</p>';
            console.error('Error loading days:', error);
        }
    }
    
    // Загрузка постов
    async function loadPosts(year, month, day) {
        const container = document.getElementById(`posts-${year}-${month}-${day}`);
        
        try {
            const response = await fetch(`/api/sitemap/posts/${year}/${month}/${day}`);
            const posts = await response.json();
            
            if (posts.length === 0) {
                container.innerHTML = '<p style="color: #999; padding: 8px;">Нет статей</p>';
                return;
            }
            
            let html = '';
            posts.forEach(post => {
                html += `
                    <div class="post-item">
                        <a href="${post.url}">
                            <span class="post-time">${post.time}</span>
                            <span>${post.title}</span>
                        </a>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            
        } catch (error) {
            container.innerHTML = '<p style="color: #c80000; padding: 8px;">Ошибка загрузки статей</p>';
            console.error('Error loading posts:', error);
        }
    }
});
</script>
@endsection

