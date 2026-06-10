@php
    $thumbnail = \App\Helpers\ContentHelper::getFeaturedImage($post, 'small');
@endphp

<article class="post-card">
    @if($thumbnail && !str_contains($thumbnail, 'placeholder'))
        <a href="{{ route('post', $post->post_name) }}" style="display: block;">
            <img src="{{ $thumbnail }}" 
                 alt="{{ $post->post_title }}" 
                 class="post-thumbnail"
                 loading="lazy"
                 width="400"
                 height="275">
        </a>
    @else
        <a href="{{ route('post', $post->post_name) }}" style="display: block; aspect-ratio: 16 / 9; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></a>
    @endif
    
    <div class="post-content">
        @if($post->categories->isNotEmpty())
            <div class="categories" style="margin-bottom: 10px;">
                @foreach($post->categories as $category)
                    <a href="{{ route('category', $category->term->slug) }}" class="category-tag">
                        {{ $category->term->name }}
                    </a>
                @endforeach
            </div>
        @endif
        
        <h2 class="post-title">
            <a href="{{ route('post', $post->post_name) }}">{{ $post->post_title }}</a>
        </h2>
        
        <div class="post-meta">
            <span>{{ $post->post_date->format('d.m.Y') }}</span>
            @if($post->author)
                <span> • <a href="{{ route('author', $post->author->ID) }}" style="color: inherit; text-decoration: none;">{{ $post->author->display_name }}</a></span>
            @endif
            <span> • 👁 {{ $post->getMeta('post_views_count', 0) }}</span>
        </div>
        
        <p class="post-excerpt">{{ \App\Helpers\ContentHelper::getExcerpt($post, 120) }}</p>
    </div>
</article>

