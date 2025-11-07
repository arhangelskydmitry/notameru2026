{{-- Вертикальный рекламный баннер --}}
<div class="sidebar-widget sidebar-banner">
    @banner('sidebar')
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
                $thumbnailId = $post->getMeta('_thumbnail_id');
                $thumbnail = null;
                if ($thumbnailId) {
                    $attachment = \App\Models\WordPress\Post::find($thumbnailId);
                    if ($attachment && $attachment->guid) {
                        $path = $attachment->guid;
                        if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $path)) {
                            $filename = basename($path);
                            $filename = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $filename);
                            $thumbnail = '/imgnews/' . $filename;
                        } else {
                            $thumbnail = $path;
                        }
                    }
                }
            @endphp
            
            <div class="popular-post-item">
                @if($thumbnail)
                    <a href="{{ route('post', $post->post_name) }}" class="popular-post-image">
                        <img src="{{ $thumbnail }}" alt="{{ $post->post_title }}">
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

{{-- Виджет группы ВКонтакте --}}
<div class="sidebar-widget vk-widget">
    <h3 class="widget-title">Подписывайтесь на нас ВКонтакте</h3>
    <div class="widget-content">
        <!-- VK Widget -->
        <div id="vk_groups"></div>
    </div>
</div>

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

/* VK Виджет */
.vk-widget .widget-content {
    padding: 0;
}

#vk_groups {
    min-height: 200px;
}

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

<script>
// Инициализация VK виджета
(function() {
    // Проверяем, загружен ли уже VK API
    if (typeof VK !== 'undefined') {
        initVKWidget();
    } else {
        // Загружаем VK OpenAPI
        var script = document.createElement('script');
        script.src = 'https://vk.com/js/api/openapi.js?169';
        script.async = true;
        script.onload = function() {
            VK.init({
                apiId: 51890478, 
                onlyWidgets: true
            });
            initVKWidget();
        };
        document.head.appendChild(script);
    }
    
    function initVKWidget() {
        VK.Widgets.Group("vk_groups", {
            mode: 3,           // Режим: 3 = компактный с кнопкой подписки
            width: "auto",     // Автоматическая ширина
            height: "700",     // Высота виджета (как в оригинале)
            color1: 'FFFFFF',  // Цвет фона
            color2: '000000',  // Цвет текста
            color3: 'c80000'   // Цвет кнопки (красный как у темы)
        }, 20913643);          // ID группы из оригинального кода
    }
})();
</script>

