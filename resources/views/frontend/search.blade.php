@extends('frontend.layout')

@php
    $seoMeta = app(\App\Services\SeoMetaService::class)->forSearch($query, $posts);
@endphp

@section('title', $seoMeta['title'])
@section('description', $seoMeta['description'])
@section('keywords', $seoMeta['keywords'])
@section('canonical', $seoMeta['canonical'])
@section('robots', $seoMeta['robots'])

@section('og_type', $seoMeta['og']['type'])
@section('og_title', $seoMeta['og']['title'])
@section('og_description', $seoMeta['og']['description'])
@section('og_url', $seoMeta['og']['url'])
@section('og_image', $seoMeta['og']['image'])

@section('twitter_card', $seoMeta['twitter']['card'])
@section('twitter_title', $seoMeta['twitter']['title'])
@section('twitter_description', $seoMeta['twitter']['description'])
@section('twitter_image', $seoMeta['twitter']['image'])

@section('breadcrumbs')
    <a href="{{ route('home') }}">Главная</a>
    <span class="separator">›</span>
    <span class="current">Поиск: {{ Str::limit($query, 40) }}</span>
@endsection

@section('content')
<!-- Умное поле поиска -->
<div class="smart-search-wrapper">
    <form action="{{ route('search') }}" method="GET" class="smart-search-form">
        <div class="search-input-wrapper">
            <input 
                type="text" 
                name="q" 
                id="smart-search-input"
                value="{{ $query }}" 
                placeholder="Поиск по сайту..."
                autocomplete="off"
                class="smart-search-input"
            >
            <button type="submit" class="smart-search-button">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </button>
            
            <!-- Выпадающий список с подсказками -->
            <div id="search-suggestions" class="search-suggestions"></div>
        </div>
    </form>
</div>

<div style="margin-bottom: 30px;">
    <h1 style="font-size: 36px; color: #2c3e50;">Результаты поиска: "{{ $query }}"</h1>
    <p style="font-size: 14px; color: #999; margin-top: 10px;">Найдено: {{ count($posts) }}+ {{ \App\Helpers\ContentHelper::pluralize(count($posts), ['статья', 'статьи', 'статей']) }}</p>
</div>

@if($posts->isEmpty())
    <div style="background: #fff; padding: 60px; text-align: center; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <p style="font-size: 20px; color: #999;">По вашему запросу ничего не найдено</p>
        <p style="margin-top: 10px; color: #999;">Попробуйте другие ключевые слова</p>
    </div>
@else
    <div class="posts-grid" id="posts-container" data-query="{{ $query }}">
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
        const searchQuery = postsContainer.dataset.query;
        
        async function loadMorePosts() {
            if (isLoading || totalPostsReached) return;
            
            isLoading = true;
            loadingIndicator.style.display = 'block';
            
            try {
                const response = await fetch(`/api/load-more-posts?offset=${offset}&limit=${limit}&search=${encodeURIComponent(searchQuery)}`);
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

<!-- Стили и скрипт для умного поиска -->
<style>
.smart-search-wrapper {
    margin-bottom: 30px;
}

.smart-search-form {
    max-width: 100%;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    background: #fff;
    border: 2px solid #e5e5e5;
    border-radius: 8px;
    padding: 0;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.search-input-wrapper:focus-within {
    border-color: #c80000;
    box-shadow: 0 0 0 3px rgba(200, 0, 0, 0.1);
}

.smart-search-input {
    flex: 1;
    border: none;
    padding: 15px 20px;
    font-size: 16px;
    outline: none;
    background: transparent;
    color: #333;
}

.smart-search-input::placeholder {
    color: #999;
}

.smart-search-button {
    background: #c80000;
    border: none;
    padding: 12px 20px;
    margin: 4px;
    border-radius: 6px;
    cursor: pointer;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s ease;
}

.smart-search-button:hover {
    background: #a00000;
}

.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e5e5e5;
    border-top: none;
    border-radius: 0 0 8px 8px;
    max-height: 400px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.search-suggestions.active {
    display: block;
}

.suggestion-item {
    padding: 12px 20px;
    cursor: pointer;
    border-bottom: 1px solid #f5f5f5;
    transition: background 0.2s ease;
    display: flex;
    align-items: center;
    gap: 12px;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover,
.suggestion-item.active {
    background: #f8f8f8;
}

.suggestion-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
}

.suggestion-content {
    flex: 1;
}

.suggestion-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
    line-height: 1.4;
}

.suggestion-meta {
    font-size: 12px;
    color: #999;
}

.suggestion-highlight {
    background: #fff3cd;
    padding: 1px 2px;
    border-radius: 2px;
}

.search-loading {
    padding: 20px;
    text-align: center;
    color: #999;
}

@media (max-width: 768px) {
    .smart-search-input {
        font-size: 14px;
        padding: 12px 15px;
    }
    
    .smart-search-button {
        padding: 10px 15px;
    }
    
    .suggestion-image {
        width: 50px;
        height: 50px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('smart-search-input');
    const suggestionsBox = document.getElementById('search-suggestions');
    let debounceTimer;
    let currentFocus = -1;
    
    // Умный поиск с дебаунсингом
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            hideSuggestions();
            return;
        }
        
        debounceTimer = setTimeout(() => {
            fetchSuggestions(query);
        }, 300);
    });
    
    // Навигация клавиатурой
    searchInput.addEventListener('keydown', function(e) {
        const items = suggestionsBox.querySelectorAll('.suggestion-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentFocus++;
            if (currentFocus >= items.length) currentFocus = 0;
            setActive(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentFocus--;
            if (currentFocus < 0) currentFocus = items.length - 1;
            setActive(items);
        } else if (e.key === 'Enter') {
            if (currentFocus > -1 && items[currentFocus]) {
                e.preventDefault();
                items[currentFocus].click();
            }
        } else if (e.key === 'Escape') {
            hideSuggestions();
        }
    });
    
    function setActive(items) {
        items.forEach(item => item.classList.remove('active'));
        if (items[currentFocus]) {
            items[currentFocus].classList.add('active');
            items[currentFocus].scrollIntoView({ block: 'nearest' });
        }
    }
    
    // Получение подсказок
    async function fetchSuggestions(query) {
        suggestionsBox.innerHTML = '<div class="search-loading">Поиск...</div>';
        suggestionsBox.classList.add('active');
        
        try {
            const response = await fetch(`/api/search-suggestions?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            if (data.length === 0) {
                suggestionsBox.innerHTML = '<div class="search-loading">Ничего не найдено</div>';
                return;
            }
            
            displaySuggestions(data, query);
        } catch (error) {
            console.error('Ошибка поиска:', error);
            hideSuggestions();
        }
    }
    
    // Отображение подсказок
    function displaySuggestions(suggestions, query) {
        currentFocus = -1;
        const html = suggestions.map(item => {
            const title = highlightText(item.title, query);
            const image = item.image || '/images/placeholder.jpg';
            
            return `
                <div class="suggestion-item" data-url="${item.url}">
                    ${item.image ? `<img src="${image}" alt="${item.title}" class="suggestion-image">` : ''}
                    <div class="suggestion-content">
                        <div class="suggestion-title">${title}</div>
                        <div class="suggestion-meta">${item.date} • 👁 ${item.views}</div>
                    </div>
                </div>
            `;
        }).join('');
        
        suggestionsBox.innerHTML = html;
        
        // Добавляем клики на подсказки
        suggestionsBox.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', function() {
                window.location.href = this.dataset.url;
            });
        });
    }
    
    // Подсветка совпадений
    function highlightText(text, query) {
        const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
        return text.replace(regex, '<span class="suggestion-highlight">$1</span>');
    }
    
    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    // Скрытие подсказок
    function hideSuggestions() {
        suggestionsBox.classList.remove('active');
        currentFocus = -1;
    }
    
    // Клик вне поля поиска
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            hideSuggestions();
        }
    });
});
</script>
@endsection

