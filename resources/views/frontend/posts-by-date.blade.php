@extends('frontend.layout')

@php
    $seoMeta = app(\App\Services\SeoMetaService::class)
        ->forDateArchive($formattedDate, $posts, $description ?? null);

    $canonicalUrl = $seoMeta['canonical'];
    $collectionSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Посты за ' . $formattedDate,
        'description' => $seoMeta['description'],
        'url' => $canonicalUrl,
    ];
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Главная',
                'item' => route('home'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Архив',
                'item' => route('home'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $formattedDate,
                'item' => $canonicalUrl,
            ],
        ],
    ];
    $itemListSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Публикации за ' . $formattedDate,
        'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
        'numberOfItems' => $posts->count(),
        'itemListElement' => $posts->values()->map(function ($post, $index) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('post', $post->post_name),
                'name' => $post->post_title,
            ];
        })->all(),
    ];
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

<script type="application/ld+json">@json($collectionSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</script>
<script type="application/ld+json">@json($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</script>
<script type="application/ld+json">@json($itemListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</script>

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
    <h1 style="font-size: 32px; margin-bottom: 10px; color: #2c3e50;">
        Посты за {{ $formattedDate }}
    </h1>
    
    <p style="color: #666; margin-bottom: 30px; font-size: 16px;">
        Найдено публикаций: <span id="total-posts">{{ $posts->total() }}</span>
    </p>
    
    @if($posts->isEmpty())
        <div style="background: #f8f9fa; padding: 40px; border-radius: 12px; text-align: center;">
            <p style="font-size: 18px; color: #666; margin: 0;">
                За эту дату нет опубликованных постов
            </p>
        </div>
    @else
        <div id="posts-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; margin-bottom: 40px;">
            @foreach($posts as $post)
                @php
                    $thumbnail = \App\Helpers\ContentHelper::getFeaturedImage($post, 'small');
                @endphp
                
                <article class="post-card" style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <a href="{{ route('post', $post->post_name) }}" style="display: block;">
                        <div style="aspect-ratio: 16 / 9; background: #f4f4f4; overflow: hidden;">
                            <img src="{{ $thumbnail }}" 
                                 alt="{{ $post->post_title }}"
                                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                                 onmouseover="this.style.transform='scale(1.05)'"
                                 onmouseout="this.style.transform='scale(1)'">
                        </div>
                    </a>
                    
                    <div style="padding: 20px;">
                        <h2 style="margin: 0 0 10px 0; font-size: 18px; line-height: 1.4;">
                            <a href="{{ route('post', $post->post_name) }}" 
                               style="color: #2c3e50; text-decoration: none; transition: color 0.3s ease;"
                               onmouseover="this.style.color='#c80000'"
                               onmouseout="this.style.color='#2c3e50'">
                                {{ $post->post_title }}
                            </a>
                        </h2>
                        
                        <div style="font-size: 14px; color: #999; margin-bottom: 10px;">
                            {{ $post->post_date->format('d.m.Y H:i') }}
                        </div>
                        
                        @if($post->categories->isNotEmpty())
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 15px;">
                                @foreach($post->categories->take(3) as $category)
                                    <a href="{{ route('category', $category->term->slug) }}" 
                                       style="background: #f0f0f0; color: #666; padding: 4px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; transition: background 0.3s ease;"
                                       onmouseover="this.style.background='#c80000'; this.style.color='#fff'"
                                       onmouseout="this.style.background='#f0f0f0'; this.style.color='#666'">
                                        {{ $category->term->name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        
        {{-- Индикатор загрузки --}}
        <div id="loading-indicator" style="display: none; text-align: center; padding: 20px;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #c80000; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 10px; color: #999;">Загрузка...</p>
        </div>
        
        {{-- Скрытые данные для пагинации --}}
        <div id="pagination-data" 
             data-current-page="{{ $posts->currentPage() }}"
             data-last-page="{{ $posts->lastPage() }}"
             data-base-url="{{ $posts->url(1) }}"
             style="display: none;">
        </div>
    @endif
    
    <div style="margin-top: 40px; text-align: center;">
        <a href="{{ route('home') }}" 
           style="display: inline-block; padding: 12px 30px; background: #c80000; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 500; transition: background 0.3s ease;"
           onmouseover="this.style.background='#a00000'"
           onmouseout="this.style.background='#c80000'">
            ← Вернуться на главную
        </a>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paginationData = document.getElementById('pagination-data');
    if (!paginationData) return;
    
    let currentPage = parseInt(paginationData.dataset.currentPage);
    const lastPage = parseInt(paginationData.dataset.lastPage);
    const baseUrl = paginationData.dataset.baseUrl;
    let isLoading = false;
    
    // Функция для загрузки следующей страницы
    async function loadNextPage() {
        if (isLoading || currentPage >= lastPage) return;
        
        isLoading = true;
        const loadingIndicator = document.getElementById('loading-indicator');
        loadingIndicator.style.display = 'block';
        
        try {
            const nextPage = currentPage + 1;
            const url = baseUrl.replace(/page=\d+/, `page=${nextPage}`);
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) throw new Error('Failed to load');
            
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newPosts = doc.querySelectorAll('.post-card');
            
            if (newPosts.length > 0) {
                const container = document.getElementById('posts-container');
                newPosts.forEach(post => {
                    container.appendChild(post.cloneNode(true));
                });
                
                currentPage = nextPage;
            }
        } catch (error) {
            console.error('Error loading posts:', error);
        } finally {
            loadingIndicator.style.display = 'none';
            isLoading = false;
        }
    }
    
    // Infinite scroll
    let scrollTimeout;
    window.addEventListener('scroll', function() {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(function() {
            const scrollPosition = window.innerHeight + window.scrollY;
            const pageHeight = document.documentElement.scrollHeight;
            
            // Загружаем когда до конца страницы осталось 300px
            if (scrollPosition >= pageHeight - 300 && !isLoading && currentPage < lastPage) {
                loadNextPage();
            }
        }, 100);
    });
});
</script>
@endsection

