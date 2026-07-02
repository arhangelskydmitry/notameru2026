@extends('frontend.layout')

@section('title', 'Страница не найдена - 404 | Нота Миру')
@section('description', 'К сожалению, запрашиваемая страница не найдена. Посмотрите популярные статьи или ознакомьтесь с последними новостями.')
@section('robots', 'noindex, follow')

{{-- Open Graph --}}
@section('og_title', 'Страница не найдена - 404')
@section('og_description', 'К сожалению, запрашиваемая страница не найдена. Посмотрите популярные статьи.')
@section('og_type', 'website')

@section('content')
<div class="not-found-page">
    <!-- Hero Section с анимацией -->
    <div class="error-hero">
        <div class="error-code">404</div>
        <h1 class="error-title">Страница не найдена</h1>
        <p class="error-description">
            К сожалению, запрашиваемая страница не существует или была перемещена.<br>
            Предлагаем ознакомиться с нашими популярными материалами!
        </p>
        <a href="{{ route('home') }}" class="btn-home">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            Вернуться на главную
        </a>
    </div>

    <!-- Популярные статьи - Слайдер -->
    @if($popularPosts->count() > 0)
    <div class="popular-section">
        <h2 class="section-title">🔥 Популярное сегодня</h2>
        
        <div class="popular-slider-container">
            <button class="slider-nav prev" id="prevSlide">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            
            <div class="popular-slider">
                <div class="slider-track" id="sliderTrack">
                    @foreach($popularPosts as $post)
                    <div class="slide">
                        <a href="{{ route('post', $post->post_name) }}" class="popular-card">
                            @if($post->featured_image)
                            <div class="popular-card-image">
                                <img src="{{ $post->featured_image }}" 
                                     alt="{{ $post->post_title }}"
                                     loading="lazy">
                                <div class="popular-badge">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    Популярное
                                </div>
                            </div>
                            @endif
                            
                            <div class="popular-card-content">
                                @if($post->categories->first())
                                <span class="popular-category">
                                    {{ $post->categories->first()->term->name }}
                                </span>
                                @endif
                                
                                <h3 class="popular-title">{{ $post->post_title }}</h3>
                                
                                @if($post->post_excerpt)
                                <p class="popular-excerpt">{{ Str::limit(strip_tags($post->post_excerpt), 120) }}</p>
                                @endif
                                
                                <div class="popular-meta">
                                    <span class="popular-date">
                                        {{ \Carbon\Carbon::parse($post->post_date)->format('d.m.Y') }}
                                    </span>
                                    @if($post->author)
                                    <span class="popular-author">{{ $post->author->display_name }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            
            <button class="slider-nav next" id="nextSlide">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>
        
        <div class="slider-dots" id="sliderDots"></div>
    </div>
    @endif

    <!-- Последние новости с бесконечной прокруткой -->
    <div class="latest-section">
        <h2 class="section-title">📰 Последние новости</h2>
        
        <div class="posts-grid" id="postsGrid">
            @foreach($latestPosts as $post)
            <article class="post-card">
                <a href="{{ route('post', $post->post_name) }}" class="post-card-link">
                    @if($post->featured_image)
                    <div class="post-card-image">
                        <img src="{{ $post->featured_image }}" 
                             alt="{{ $post->post_title }}"
                             loading="lazy">
                    </div>
                    @endif
                    
                    <div class="post-card-content">
                        @if($post->categories->first())
                        <span class="post-category" style="background: {{ $post->categories->first()->term->category_color ?? '#c80000' }}">
                            {{ $post->categories->first()->term->name }}
                        </span>
                        @endif
                        
                        <h3 class="post-title">{{ $post->post_title }}</h3>
                        
                        @if($post->post_excerpt)
                        <p class="post-excerpt">{{ Str::limit(strip_tags($post->post_excerpt), 150) }}</p>
                        @endif
                        
                        <div class="post-meta">
                            <span class="post-date">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                {{ \Carbon\Carbon::parse($post->post_date)->format('d.m.Y') }}
                            </span>
                            @if($post->author)
                            <span class="post-author">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                {{ $post->author->display_name }}
                            </span>
                            @endif
                        </div>
                    </div>
                </a>
            </article>
            @endforeach
        </div>
        
        <div class="loading-indicator" id="loadingIndicator" style="display: none;">
            <div class="spinner"></div>
            <p>Загрузка новостей...</p>
        </div>
        
        <div class="end-message" id="endMessage" style="display: none;">
            <p>✨ Вы просмотрели все доступные новости</p>
        </div>
    </div>
</div>

<style>
/* Hero Section */
.error-hero {
    text-align: center;
    padding: 80px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    margin: 40px 0;
    box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3);
}

.error-code {
    font-size: 120px;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 20px;
    background: linear-gradient(45deg, #fff, #f0f0f0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: glitch 2s infinite;
}

@keyframes glitch {
    0%, 100% { transform: translate(0); }
    20% { transform: translate(-2px, 2px); }
    40% { transform: translate(-2px, -2px); }
    60% { transform: translate(2px, 2px); }
    80% { transform: translate(2px, -2px); }
}

.error-title {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 16px;
}

.error-description {
    font-size: 18px;
    line-height: 1.6;
    margin-bottom: 32px;
    opacity: 0.95;
}

.btn-home {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 32px;
    background: white;
    color: #667eea;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.btn-home:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 30px rgba(0,0,0,0.15);
}

/* Popular Section */
.popular-section {
    margin: 60px 0;
}

.section-title {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 32px;
    text-align: center;
}

.popular-slider-container {
    position: relative;
    margin: 0 60px;
}

.popular-slider {
    overflow: hidden;
    border-radius: 16px;
}

.slider-track {
    display: flex;
    transition: transform 0.5s ease;
    gap: 20px;
}

.slide {
    min-width: 400px;
    flex-shrink: 0;
}

.popular-card {
    display: block;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s;
    text-decoration: none;
    color: inherit;
}

.popular-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.popular-card-image {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.popular-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.popular-card:hover .popular-card-image img {
    transform: scale(1.05);
}

.popular-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: rgba(255, 215, 0, 0.95);
    color: #333;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.popular-card-content {
    padding: 24px;
}

.popular-category {
    display: inline-block;
    background: #c80000;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 12px;
}

.popular-title {
    font-size: 20px;
    font-weight: 700;
    line-height: 1.4;
    margin-bottom: 12px;
    color: #1a1a1a;
}

.popular-excerpt {
    font-size: 14px;
    line-height: 1.6;
    color: #666;
    margin-bottom: 16px;
}

.popular-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 13px;
    color: #999;
}

.slider-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    border: none;
    border-radius: 50%;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.3s;
    z-index: 10;
}

.slider-nav:hover {
    background: #c80000;
    color: white;
    transform: translateY(-50%) scale(1.1);
}

.slider-nav.prev {
    left: -60px;
}

.slider-nav.next {
    right: -60px;
}

.slider-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
}

.slider-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #ddd;
    cursor: pointer;
    transition: all 0.3s;
}

.slider-dot.active {
    background: #c80000;
    width: 24px;
    border-radius: 5px;
}

/* Latest Section */
.latest-section {
    margin: 60px 0;
}

.posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.post-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.post-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.post-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.post-card-image {
    height: 200px;
    overflow: hidden;
}

.post-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.post-card:hover .post-card-image img {
    transform: scale(1.05);
}

.post-card-content {
    padding: 20px;
}

.post-category {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    color: white;
    margin-bottom: 10px;
}

.post-title {
    font-size: 18px;
    font-weight: 700;
    line-height: 1.4;
    margin-bottom: 10px;
    color: #1a1a1a;
}

.post-excerpt {
    font-size: 14px;
    line-height: 1.6;
    color: #666;
    margin-bottom: 12px;
}

.post-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    color: #999;
}

.post-meta svg {
    vertical-align: middle;
}

/* Loading & End Messages */
.loading-indicator, .end-message {
    text-align: center;
    padding: 40px 20px;
}

.spinner {
    width: 40px;
    height: 40px;
    margin: 0 auto 16px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #c80000;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.end-message p {
    font-size: 18px;
    color: #666;
}

/* Responsive */
@media (max-width: 768px) {
    .error-code {
        font-size: 80px;
    }
    
    .error-title {
        font-size: 28px;
    }
    
    .error-description {
        font-size: 16px;
    }
    
    .popular-slider-container {
        margin: 0 40px;
    }
    
    .slide {
        min-width: 300px;
    }
    
    .slider-nav {
        width: 40px;
        height: 40px;
    }
    
    .slider-nav.prev {
        left: -40px;
    }
    
    .slider-nav.next {
        right: -40px;
    }
    
    .posts-grid {
        grid-template-columns: 1fr;
    }
    
    .section-title {
        font-size: 24px;
    }
}

@media (max-width: 480px) {
    .error-hero {
        padding: 60px 20px;
    }
    
    .popular-slider-container {
        margin: 0;
    }
    
    .slider-nav {
        display: none;
    }
    
    .slide {
        min-width: 100%;
    }
}
</style>

<script>
// Слайдер
let currentSlide = 0;
const track = document.getElementById('sliderTrack');
const slides = document.querySelectorAll('.slide');
const dotsContainer = document.getElementById('sliderDots');
const totalSlides = slides.length;

// Создаем точки
for (let i = 0; i < totalSlides; i++) {
    const dot = document.createElement('div');
    dot.classList.add('slider-dot');
    if (i === 0) dot.classList.add('active');
    dot.addEventListener('click', () => goToSlide(i));
    dotsContainer.appendChild(dot);
}

function goToSlide(index) {
    currentSlide = index;
    const slideWidth = slides[0].offsetWidth + 20; // +20 для gap
    track.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
    
    // Обновляем точки
    document.querySelectorAll('.slider-dot').forEach((dot, i) => {
        dot.classList.toggle('active', i === currentSlide);
    });
}

document.getElementById('prevSlide')?.addEventListener('click', () => {
    currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
    goToSlide(currentSlide);
});

document.getElementById('nextSlide')?.addEventListener('click', () => {
    currentSlide = (currentSlide + 1) % totalSlides;
    goToSlide(currentSlide);
});

// Автопрокрутка
setInterval(() => {
    currentSlide = (currentSlide + 1) % totalSlides;
    goToSlide(currentSlide);
}, 5000);

// Бесконечная прокрутка
let offset = {{ $latestPosts->count() }};
let loading = false;
let hasMore = offset < {{ $totalPosts }};

function loadMorePosts() {
    if (loading || !hasMore) return;
    
    loading = true;
    document.getElementById('loadingIndicator').style.display = 'block';
    
    fetch(`/api/load-more-posts?offset=${offset}&limit=12`)
        .then(response => response.json())
        .then(data => {
            const grid = document.getElementById('postsGrid');
            
            data.posts.forEach(post => {
                const article = document.createElement('article');
                article.classList.add('post-card');
                article.style.opacity = '0';
                article.style.transform = 'translateY(20px)';
                
                const category = post.categories?.[0];
                const categoryHtml = category ? 
                    `<span class="post-category" style="background: ${category.term.category_color || '#c80000'}">${category.term.name}</span>` : '';
                
                const imageHtml = post.featured_image ? 
                    `<div class="post-card-image">
                        <img src="${post.featured_image}" alt="${post.post_title}" loading="lazy">
                    </div>` : '';
                
                const excerptHtml = post.post_excerpt ? 
                    `<p class="post-excerpt">${post.post_excerpt.substring(0, 150)}...</p>` : '';
                
                const authorHtml = post.author ? 
                    `<span class="post-author">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        ${post.author.display_name}
                    </span>` : '';
                
                article.innerHTML = `
                    <a href="/${post.post_name}" class="post-card-link">
                        ${imageHtml}
                        <div class="post-card-content">
                            ${categoryHtml}
                            <h3 class="post-title">${post.post_title}</h3>
                            ${excerptHtml}
                            <div class="post-meta">
                                <span class="post-date">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    ${post.formatted_date}
                                </span>
                                ${authorHtml}
                            </div>
                        </div>
                    </a>
                `;
                
                grid.appendChild(article);
                
                // Анимация появления
                setTimeout(() => {
                    article.style.transition = 'all 0.5s ease';
                    article.style.opacity = '1';
                    article.style.transform = 'translateY(0)';
                }, 50);
            });
            
            offset += data.posts.length;
            hasMore = data.has_more;
            loading = false;
            document.getElementById('loadingIndicator').style.display = 'none';
            
            if (!hasMore) {
                document.getElementById('endMessage').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error loading posts:', error);
            loading = false;
            document.getElementById('loadingIndicator').style.display = 'none';
        });
}

// Отслеживание скролла
function handleScroll() {
    const scrollPosition = window.innerHeight + window.scrollY;
    const threshold = document.documentElement.scrollHeight - 1000;
    
    if (scrollPosition >= threshold && !loading && hasMore) {
        loadMorePosts();
    }
}

window.addEventListener('scroll', handleScroll);
</script>
@endsection

