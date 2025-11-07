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
      "name": "{{ $post->post_title }}"
    }
  ]
}
</script>
@endpush

@section('content')
<div style="display: grid; grid-template-columns: 1fr 300px; gap: 40px;">
    <!-- Статья -->
    <article style="background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <header style="margin-bottom: 30px;">
            <h1 style="font-size: 36px; line-height: 1.2; margin-bottom: 20px; color: #2c3e50;">
                {{ $post->post_title }}
            </h1>
            
            @if($post->categories->isNotEmpty())
                <div class="categories" style="margin-top: 15px;">
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
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Создаем модальное окно
            const modal = document.createElement('div');
            modal.className = 'image-modal';
            modal.innerHTML = '<span class="image-modal-close">&times;</span><img src="" alt="">';
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
    </article>
    
    <!-- Сайдбар -->
    <aside>
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
    </aside>
</div>
@endsection
