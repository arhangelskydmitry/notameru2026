@extends('layouts.admin')
@section('title', 'Редактирование статьи')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-edit"></i> Редактирование статьи</h1>
        <a href="{{ route('admin.posts') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Назад к списку
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.posts.update', $post->ID) }}" method="POST">
                @csrf
                
                <!-- Название -->
                <div class="mb-3">
                    <label for="post_title" class="form-label">Название статьи</label>
                    <input type="text" 
                           class="form-control @error('post_title') is-invalid @enderror" 
                           id="post_title" 
                           name="post_title" 
                           value="{{ old('post_title', $post->post_title) }}" 
                           required>
                    @error('post_title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Slug -->
                <div class="mb-3">
                    <label for="post_name" class="form-label">URL (slug)</label>
                    <input type="text" 
                           class="form-control" 
                           id="post_name" 
                           value="{{ $post->post_name }}" 
                           readonly>
                    @if($post->post_name && $post->post_status == 'publish')
                        <small class="text-muted">Постоянная ссылка: <a href="{{ route('post', $post->post_name) }}" target="_blank">{{ route('post', $post->post_name) }}</a></small>
                    @else
                        <small class="text-muted text-warning">
                            @if(!$post->post_name)
                                ⚠️ Slug отсутствует - пост не доступен на фронтенде
                            @else
                                Пост не опубликован
                            @endif
                        </small>
                    @endif
                </div>
                
                <!-- Контент -->
                <div class="mb-3">
                    <label for="post_content" class="form-label">Содержание</label>
                    <textarea class="form-control tinymce-editor @error('post_content') is-invalid @enderror" 
                              id="post_content" 
                              name="post_content" 
                              rows="15" 
                              required>{{ old('post_content', $post->post_content) }}</textarea>
                    @error('post_content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Используйте визуальный редактор для форматирования текста</small>
                </div>
                
                <!-- Краткое описание -->
                <div class="mb-3">
                    <label for="post_excerpt" class="form-label">Краткое описание (excerpt)</label>
                    <textarea class="form-control @error('post_excerpt') is-invalid @enderror" 
                              id="post_excerpt" 
                              name="post_excerpt" 
                              rows="3">{{ old('post_excerpt', $post->post_excerpt) }}</textarea>
                    @error('post_excerpt')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Краткое описание статьи для превью в списках</small>
                </div>
                
                <!-- Статус -->
                <div class="mb-3">
                    <label for="post_status" class="form-label">Статус</label>
                    <select class="form-select @error('post_status') is-invalid @enderror" 
                            id="post_status" 
                            name="post_status" 
                            required>
                        <option value="publish" {{ old('post_status', $post->post_status) == 'publish' ? 'selected' : '' }}>Опубликовано</option>
                        <option value="draft" {{ old('post_status', $post->post_status) == 'draft' ? 'selected' : '' }}>Черновик</option>
                        <option value="pending" {{ old('post_status', $post->post_status) == 'pending' ? 'selected' : '' }}>Ожидает проверки</option>
                    </select>
                    @error('post_status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Категории -->
                <div class="mb-3">
                    <label class="form-label">Категории</label>
                    <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                        @foreach($categories as $category)
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="category_ids[]" 
                                       value="{{ $category->term_taxonomy_id }}" 
                                       id="category_{{ $category->term_taxonomy_id }}"
                                       {{ $post->categories->contains('term_taxonomy_id', $category->term_taxonomy_id) ? 'checked' : '' }}>
                                <label class="form-check-label" for="category_{{ $category->term_taxonomy_id }}">
                                    {{ $category->term->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- SEO настройки -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-search"></i> SEO оптимизация
                        </div>
                        @php
                            $seoService = app(\App\Services\SeoService::class);
                            $seoScore = $seoService->analyzeSeoScore($post);
                        @endphp
                        <span class="badge 
                            @if($seoScore['status'] === 'excellent') bg-success
                            @elseif($seoScore['status'] === 'good') bg-info
                            @elseif($seoScore['status'] === 'fair') bg-warning
                            @else bg-danger
                            @endif">
                            SEO Score: {{ $seoScore['score'] }}/100
                        </span>
                    </div>
                    <div class="card-body">
                        <!-- SEO анализ -->
                        @if(count($seoScore['issues']) > 0)
                        <div class="alert alert-danger">
                            <strong><i class="fas fa-exclamation-triangle"></i> Критичные проблемы:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($seoScore['issues'] as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        
                        @if(count($seoScore['recommendations']) > 0)
                        <div class="alert alert-warning">
                            <strong><i class="fas fa-lightbulb"></i> Рекомендации:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($seoScore['recommendations'] as $recommendation)
                                    <li>{{ $recommendation }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        
                        @if($seoScore['score'] >= 80)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Отличная SEO оптимизация!
                        </div>
                        @endif
                        
                        <!-- SEO Title -->
                        <div class="mb-3">
                            <label for="seo_title" class="form-label">
                                SEO Заголовок
                                <small class="text-muted">(Title)</small>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="seo_title" 
                                   name="seo_title" 
                                   value="{{ old('seo_title', $post->seo_title) }}"
                                   maxlength="255"
                                   placeholder="Оставьте пустым для использования названия статьи">
                            <small class="text-muted">
                                <span id="seo_title_length">{{ mb_strlen($post->seo_title ?: $post->post_title) }}</span> символов
                                (рекомендуется 50-60)
                            </small>
                        </div>
                        
                        <!-- SEO Description -->
                        <div class="mb-3">
                            <label for="seo_description" class="form-label">
                                SEO Описание
                                <small class="text-muted">(Meta Description)</small>
                            </label>
                            <textarea class="form-control" 
                                      id="seo_description" 
                                      name="seo_description" 
                                      rows="3"
                                      maxlength="320"
                                      placeholder="Краткое описание для поисковых систем">{{ old('seo_description', $post->seo_description) }}</textarea>
                            <small class="text-muted">
                                <span id="seo_description_length">{{ mb_strlen($post->seo_description ?: '') }}</span> символов
                                (рекомендуется 150-160)
                            </small>
                        </div>
                        
                        <!-- Focus Keyword -->
                        <div class="mb-3">
                            <label for="focus_keyword" class="form-label">
                                Ключевое слово
                                <small class="text-muted">(Focus Keyword)</small>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="focus_keyword" 
                                   name="focus_keyword" 
                                   value="{{ old('focus_keyword', $post->focus_keyword) }}"
                                   placeholder="Основное ключевое слово статьи">
                            <small class="text-muted">Ключевое слово, по которому оптимизирована статья</small>
                        </div>
                        
                        <!-- SEO Keywords -->
                        <div class="mb-3">
                            <label for="seo_keywords" class="form-label">
                                Ключевые слова
                                <small class="text-muted">(Meta Keywords)</small>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="seo_keywords" 
                                   name="seo_keywords" 
                                   value="{{ old('seo_keywords', $post->seo_keywords) }}"
                                   placeholder="Ключевое слово 1, Ключевое слово 2, ...">
                            <small class="text-muted">Разделяйте ключевые слова запятыми</small>
                        </div>
                        
                        <!-- Canonical URL -->
                        <div class="mb-3">
                            <label for="canonical_url" class="form-label">
                                Canonical URL
                                <small class="text-muted">(необязательно)</small>
                            </label>
                            <input type="url" 
                                   class="form-control" 
                                   id="canonical_url" 
                                   name="canonical_url" 
                                   value="{{ old('canonical_url', $post->canonical_url) }}"
                                   placeholder="{{ $post->post_name ? route('post', $post->post_name) : 'Автоматический URL после публикации' }}">
                            <small class="text-muted">Оставьте пустым для автоматического URL</small>
                        </div>
                        
                        <!-- Meta Robots -->
                        <div class="mb-3">
                            <label for="meta_robots" class="form-label">
                                Meta Robots
                                <small class="text-muted">(индексация)</small>
                            </label>
                            <select class="form-select" id="meta_robots" name="meta_robots">
                                <option value="index, follow" {{ old('meta_robots', $post->meta_robots) == 'index, follow' ? 'selected' : '' }}>
                                    Index, Follow (индексировать и следовать по ссылкам)
                                </option>
                                <option value="noindex, follow" {{ old('meta_robots', $post->meta_robots) == 'noindex, follow' ? 'selected' : '' }}>
                                    NoIndex, Follow (не индексировать, но следовать)
                                </option>
                                <option value="index, nofollow" {{ old('meta_robots', $post->meta_robots) == 'index, nofollow' ? 'selected' : '' }}>
                                    Index, NoFollow (индексировать, но не следовать)
                                </option>
                                <option value="noindex, nofollow" {{ old('meta_robots', $post->meta_robots) == 'noindex, nofollow' ? 'selected' : '' }}>
                                    NoIndex, NoFollow (не индексировать и не следовать)
                                </option>
                            </select>
                        </div>
                        
                        <!-- Дополнительные настройки (collapsed) -->
                        <div class="accordion" id="seoAdvanced">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOpenGraph">
                                        <i class="fas fa-share-alt me-2"></i> Open Graph (соцсети)
                                    </button>
                                </h2>
                                <div id="collapseOpenGraph" class="accordion-collapse collapse" data-bs-parent="#seoAdvanced">
                                    <div class="accordion-body">
                                        <!-- OG Title -->
                                        <div class="mb-3">
                                            <label for="og_title" class="form-label">OG:Title</label>
                                            <input type="text" class="form-control" id="og_title" name="og_title" 
                                                   value="{{ old('og_title', $post->og_title) }}"
                                                   placeholder="Заголовок для Facebook/VK">
                                        </div>
                                        
                                        <!-- OG Description -->
                                        <div class="mb-3">
                                            <label for="og_description" class="form-label">OG:Description</label>
                                            <textarea class="form-control" id="og_description" name="og_description" rows="2"
                                                      placeholder="Описание для Facebook/VK">{{ old('og_description', $post->og_description) }}</textarea>
                                        </div>
                                        
                                        <!-- OG Image -->
                                        <div class="mb-3">
                                            <label for="og_image" class="form-label">OG:Image (URL)</label>
                                            <input type="url" class="form-control" id="og_image" name="og_image" 
                                                   value="{{ old('og_image', $post->og_image) }}"
                                                   placeholder="https://example.com/image.jpg">
                                            <small class="text-muted">Оставьте пустым для автоматического изображения</small>
                                        </div>
                                        
                                        <!-- OG Type -->
                                        <div class="mb-0">
                                            <label for="og_type" class="form-label">OG:Type</label>
                                            <select class="form-select" id="og_type" name="og_type">
                                                <option value="article" {{ old('og_type', $post->og_type) == 'article' ? 'selected' : '' }}>Article</option>
                                                <option value="website" {{ old('og_type', $post->og_type) == 'website' ? 'selected' : '' }}>Website</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwitter">
                                        <i class="fab fa-twitter me-2"></i> Twitter Card
                                    </button>
                                </h2>
                                <div id="collapseTwitter" class="accordion-collapse collapse" data-bs-parent="#seoAdvanced">
                                    <div class="accordion-body">
                                        <!-- Twitter Card Type -->
                                        <div class="mb-3">
                                            <label for="twitter_card" class="form-label">Card Type</label>
                                            <select class="form-select" id="twitter_card" name="twitter_card">
                                                <option value="summary_large_image" {{ old('twitter_card', $post->twitter_card) == 'summary_large_image' ? 'selected' : '' }}>
                                                    Summary Large Image
                                                </option>
                                                <option value="summary" {{ old('twitter_card', $post->twitter_card) == 'summary' ? 'selected' : '' }}>
                                                    Summary
                                                </option>
                                            </select>
                                        </div>
                                        
                                        <!-- Twitter Title -->
                                        <div class="mb-3">
                                            <label for="twitter_title" class="form-label">Twitter:Title</label>
                                            <input type="text" class="form-control" id="twitter_title" name="twitter_title" 
                                                   value="{{ old('twitter_title', $post->twitter_title) }}"
                                                   placeholder="Заголовок для Twitter">
                                        </div>
                                        
                                        <!-- Twitter Description -->
                                        <div class="mb-3">
                                            <label for="twitter_description" class="form-label">Twitter:Description</label>
                                            <textarea class="form-control" id="twitter_description" name="twitter_description" rows="2"
                                                      placeholder="Описание для Twitter">{{ old('twitter_description', $post->twitter_description) }}</textarea>
                                        </div>
                                        
                                        <!-- Twitter Image -->
                                        <div class="mb-0">
                                            <label for="twitter_image" class="form-label">Twitter:Image (URL)</label>
                                            <input type="url" class="form-control" id="twitter_image" name="twitter_image" 
                                                   value="{{ old('twitter_image', $post->twitter_image) }}"
                                                   placeholder="https://example.com/image.jpg">
                                            <small class="text-muted">Оставьте пустым для автоматического изображения</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Мета информация -->
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Информация о статье
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>ID:</strong> {{ $post->ID }}</p>
                        <p class="mb-1"><strong>Автор:</strong> {{ $post->user->display_name ?? 'Неизвестен' }}</p>
                        <p class="mb-1"><strong>Дата создания:</strong> {{ \Carbon\Carbon::parse($post->post_date)->format('d.m.Y H:i') }}</p>
                        <p class="mb-1"><strong>Последнее изменение:</strong> {{ \Carbon\Carbon::parse($post->post_modified)->format('d.m.Y H:i') }}</p>
                        <p class="mb-0"><strong>Просмотров:</strong> {{ $post->getMeta('views', 0) }}</p>
                    </div>
                </div>
                
                <!-- Кнопки действий -->
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                    @if($post->post_name && $post->post_status == 'publish')
                    <a href="{{ route('post', $post->post_name) }}" target="_blank" class="btn btn-outline-secondary">
                        <i class="fas fa-external-link-alt"></i> Просмотреть на сайте
                    </a>
                    @else
                        <button type="button" class="btn btn-outline-secondary" disabled title="Пост должен быть опубликован и иметь slug">
                            <i class="fas fa-external-link-alt"></i> Просмотреть на сайте
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Стили для TinyMCE */
    .tox-tinymce {
        border-radius: 5px;
    }
    
    .tox .tox-statusbar {
        border-bottom-left-radius: 5px;
        border-bottom-right-radius: 5px;
    }
</style>
@endpush

@push('scripts')
<!-- TinyMCE -->
<script src="{{ asset('vendor/moonshine-tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация TinyMCE
    tinymce.init({
        selector: '.tinymce-editor',
        language: 'ru',
        language_url: '{{ asset('vendor/moonshine-tinymce/langs/ru.js') }}',
        height: 500,
        menubar: true,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link image media | code fullscreen | help',
        content_style: `
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                font-size: 16px;
                line-height: 1.6;
                color: #333;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            img {
                max-width: 100%;
                height: auto;
                border-radius: 5px;
            }
            figure.wp-caption {
                margin: 20px auto;
                text-align: center;
                max-width: 100%;
            }
            figure.wp-caption img {
                width: 100%;
                height: auto;
                border-radius: 5px;
            }
            figcaption.wp-caption-text {
                margin-top: 10px;
                font-size: 14px;
                color: #666;
                font-style: italic;
            }
        `,
        
        // Настройки для изображений
        image_advtab: true,
        image_caption: true,
        
        // Автоматическая загрузка изображений
        automatic_uploads: false,
        
        // Разрешенные элементы
        extended_valid_elements: 'figure[class|style],figcaption[class|style]',
        
        // Относительные URL
        relative_urls: false,
        remove_script_host: false,
        
        // Настройки вставки
        paste_data_images: true,
        paste_as_text: false,
        
        // Форматирование
        block_formats: 'Параграф=p; Заголовок 2=h2; Заголовок 3=h3; Заголовок 4=h4; Код=pre',
        
        // Сохранение при submit формы
        setup: function(editor) {
            editor.on('change', function() {
                editor.save();
            });
        }
    });
    
    // Подсчет символов для SEO Title
    const seoTitleInput = document.getElementById('seo_title');
    const seoTitleLength = document.getElementById('seo_title_length');
    if (seoTitleInput && seoTitleLength) {
        seoTitleInput.addEventListener('input', function() {
            seoTitleLength.textContent = this.value.length || document.getElementById('post_title').value.length;
        });
    }
    
    // Подсчет символов для SEO Description
    const seoDescInput = document.getElementById('seo_description');
    const seoDescLength = document.getElementById('seo_description_length');
    if (seoDescInput && seoDescLength) {
        seoDescInput.addEventListener('input', function() {
            seoDescLength.textContent = this.value.length;
        });
    }
});
</script>
@endpush

