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
    
    <!-- Сообщение о создании и генерации SEO -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(request()->get('seo_generated') == '1')
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-robot"></i> <strong>SEO-данные сгенерированы автоматически!</strong><br>
            <small>Статья создана и сохранена в черновиках. Проверьте SEO-поля ниже и нажмите "Опубликовать" для публикации статьи.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @elseif(request()->get('seo_generated') == '0')
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <strong>Генерация SEO не удалась</strong><br>
            <small>Статья создана с базовыми SEO-данными. Пожалуйста, заполните SEO-поля вручную перед публикацией.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
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
                    @if($post->isPubliclyAccessible())
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
                    <label for="post_content" class="form-label d-flex justify-content-between align-items-center">
                        <span>Содержание</span>
                        <button type="button" class="btn btn-sm btn-primary" id="rewriteContentBtn" title="Переписать текст статьи с помощью ИИ">
                            <i class="fas fa-magic"></i> Рерайт текста (ИИ)
                        </button>
                    </label>
                        <textarea class="form-control tinymce-editor @error('post_content') is-invalid @enderror" 
                                  id="post_content" 
                                  name="post_content" 
                                  rows="15" 
                                  required>{{ old('post_content', \App\Helpers\ContentHelper::fixImagePaths($post->post_content, true)) }}</textarea>
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
                        <option value="future" {{ old('post_status', $post->post_status) == 'future' ? 'selected' : '' }}>Отложенная публикация</option>
                        <option value="pending" {{ old('post_status', $post->post_status) == 'pending' ? 'selected' : '' }}>Ожидает проверки</option>
                    </select>
                    @error('post_status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Будущая дата с публикацией автоматически сохранится как отложенная.</small>
                </div>
                
                <!-- Дата и время публикации -->
                <div class="mb-3">
                    <label for="post_date" class="form-label">
                        <i class="fas fa-calendar-alt"></i> Дата и время публикации
                    </label>
                    <input type="datetime-local" 
                           class="form-control @error('post_date') is-invalid @enderror" 
                           id="post_date" 
                           name="post_date" 
                           value="{{ old('post_date', $post->post_date->format('Y-m-d\TH:i')) }}" 
                           required>
                    @error('post_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">
                        Укажите дату и время, когда статья должна быть опубликована.
                        Текущее: {{ $post->post_date->format('d.m.Y H:i') }}
                    </small>
                </div>
                
                <!-- Автор -->
                <div class="mb-3">
                    <label for="post_author" class="form-label">
                        <i class="fas fa-user"></i> Автор
                    </label>
                    @if(admin_user() && admin_user()->isAuthor())
                        {{-- Автор может видеть только свое имя --}}
                        <input type="text" 
                               class="form-control" 
                               value="{{ $post->author->display_name }} ({{ $post->author->user_login }})" 
                               readonly>
                        <input type="hidden" name="post_author" value="{{ admin_user()->ID }}">
                        <small class="text-muted">Вы указаны как автор этой статьи</small>
                    @else
                        {{-- Админ и редактор могут выбирать автора --}}
                    <select class="form-select @error('post_author') is-invalid @enderror" 
                            id="post_author" 
                            name="post_author" 
                            required>
                        @foreach($authors as $author)
                            <option value="{{ $author->ID }}" {{ old('post_author', $post->post_author) == $author->ID ? 'selected' : '' }}>
                                {{ $author->display_name }} ({{ $author->user_login }})
                            </option>
                        @endforeach
                    </select>
                    @error('post_author')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Выберите автора статьи</small>
                    @endif
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
                
                <!-- Теги -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-tags"></i> Теги
                    </label>
                    <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                        @if($tags->isEmpty())
                            <p class="text-muted mb-0">Теги не найдены</p>
                        @else
                            @foreach($tags as $tag)
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="tag_ids[]" 
                                           value="{{ $tag->term_taxonomy_id }}" 
                                           id="tag_{{ $tag->term_taxonomy_id }}"
                                           {{ $post->tags->contains('term_taxonomy_id', $tag->term_taxonomy_id) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tag_{{ $tag->term_taxonomy_id }}">
                                        {{ $tag->term->name }}
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <small class="text-muted">Выберите теги для этой статьи</small>
                </div>
                
                <!-- Обложка поста (Featured Image) -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-image"></i> Обложка поста (Featured Image)
                    </label>
                    
                    @php
                        $displayImage = $featuredImage && !str_contains($featuredImage, 'placeholder') 
                            ? $featuredImage 
                            : ($firstImageFromContent ?? null);
                    @endphp
                    
                    <div id="featuredImagePreview" class="mb-2" style="{{ $displayImage ? '' : 'display: none;' }}">
                        <img src="{{ $displayImage ?? '' }}" alt="Featured Image" class="img-thumbnail" style="max-width: 300px; max-height: 200px; object-fit: cover;">
                    </div>
                    
                    @if(!$displayImage)
                        <div id="noFeaturedImageAlert" class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Обложка не установлена
                        </div>
                    @endif
                    
                    <div class="input-group mb-2">
                        <input type="hidden" id="featured_image_id" name="featured_image_id" value="{{ $post->getMeta('_thumbnail_id', '') }}">
                        <input type="text" class="form-control" id="featured_image_url" name="featured_image_url"
                               value="{{ $featuredImage && !str_contains($featuredImage, 'placeholder') ? $featuredImage : ($firstImageFromContent ?? '') }}"
                               placeholder="URL изображения или загрузите файл (можно использовать относительный путь /imgnews/...)">
                        <button type="button" class="btn btn-outline-primary" id="uploadFeaturedImageBtn">
                            <i class="fas fa-upload"></i> Загрузить
                        </button>
                        @if(($featuredImage && !str_contains($featuredImage, 'placeholder')) || $firstImageFromContent)
                        <button type="button" class="btn btn-outline-danger" id="removeFeaturedImageBtn">
                            <i class="fas fa-trash"></i>
                        </button>
                        @endif
                    </div>
                    <input type="file" id="featuredImageInput" accept="image/*" style="display: none;">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Укажите URL или загрузите файл. 
                        @if($firstImageFromContent && (!$featuredImage || str_contains($featuredImage, 'placeholder')))
                            <span class="text-success">Автоматически выбрано первое изображение из статьи</span>
                        @endif
                    </small>
                </div>
                
                <!-- SEO настройки -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-search"></i> SEO оптимизация
                            <button type="button" class="btn btn-sm btn-success ms-3" id="generateSeoBtn">
                                <i class="fas fa-robot"></i> Сгенерировать SEO (ИИ)
                            </button>
                        </div>
                        @php
                            $seoService = app(\App\Services\SeoService::class);
                            $seoScore = $seoService->analyzeSeoScore($post);
                        @endphp
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="syncSeoFieldsBtn" title="Синхронизировать поля">
                                <i class="fas fa-sync"></i> Синхронизировать
                            </button>
                            <span class="badge 
                                @if($seoScore['status'] === 'excellent') bg-success
                                @elseif($seoScore['status'] === 'good') bg-info
                                @elseif($seoScore['status'] === 'fair') bg-warning
                                @else bg-danger
                                @endif">
                                SEO Score: {{ $seoScore['score'] }}/100
                            </span>
                        </div>
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
                                   value="{{ old('focus_keyword', is_array($post->focus_keyword) ? implode(', ', $post->focus_keyword) : $post->focus_keyword) }}"
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
                                   value="{{ old('seo_keywords', is_array($post->seo_keywords) ? implode(', ', $post->seo_keywords) : $post->seo_keywords) }}"
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
                                            <label for="og_image" class="form-label">
                                                <i class="fas fa-image"></i> SEO Изображение (OG:Image)
                                            </label>
                                            
                                            @if($post->og_image)
                                                <div class="mb-2">
                                                    <img src="{{ $post->og_image }}" alt="SEO Image" style="max-width: 300px; border: 1px solid #ddd; border-radius: 4px;">
                                                </div>
                                            @endif
                                            
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" id="og_image" name="og_image" 
                                                       value="{{ old('og_image', $post->og_image) }}"
                                                       placeholder="https://example.com/image.jpg или /imgnews/... или оставьте пустым">
                                                <button type="button" class="btn btn-outline-primary" id="uploadSeoImageBtn">
                                                    <i class="fas fa-upload"></i> Загрузить
                                                </button>
                                            </div>
                                            <input type="file" id="seoImageInput" accept="image/*" style="display: none;">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle"></i> Если не указано, будет использовано первое изображение из контента
                                            </small>
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
                                            <label for="twitter_image" class="form-label">
                                                <i class="fas fa-image"></i> Twitter:Image
                                            </label>
                                            
                                            @if($post->twitter_image)
                                                <div class="mb-2">
                                                    <img src="{{ $post->twitter_image }}" alt="Twitter Image" style="max-width: 300px; border: 1px solid #ddd; border-radius: 4px;">
                                                </div>
                                            @endif
                                            
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" id="twitter_image" name="twitter_image" 
                                                       value="{{ old('twitter_image', $post->twitter_image) }}"
                                                       placeholder="https://example.com/image.jpg или /imgnews/... или оставьте пустым">
                                                <button type="button" class="btn btn-outline-primary" id="uploadTwitterImageBtn">
                                                    <i class="fas fa-upload"></i> Загрузить
                                                </button>
                                            </div>
                                            <input type="file" id="twitterImageInput" accept="image/*" style="display: none;">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle"></i> Если не указано, будет использовано первое изображение из контента
                                            </small>
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
                        <p class="mb-0"><strong>Просмотров:</strong> {{ $post->getMeta('post_views_count', 0) }}</p>
                    </div>
                </div>
                
                <!-- Кнопки действий -->
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                    @if($post->isPubliclyAccessible())
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

<script>
// Загрузка обложки поста (Featured Image)
document.getElementById('uploadFeaturedImageBtn')?.addEventListener('click', function() {
    document.getElementById('featuredImageInput').click();
});

document.getElementById('featuredImageInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        uploadFeaturedImage(file);
    }
});

// Удаление обложки
document.getElementById('removeFeaturedImageBtn')?.addEventListener('click', function() {
    if (confirm('Удалить обложку поста?')) {
        document.getElementById('featured_image_id').value = '';
        document.getElementById('featured_image_url').value = '';
        
        const preview = document.getElementById('featuredImagePreview');
        preview.style.display = 'none';
        
        const alertElement = document.getElementById('noFeaturedImageAlert');
        if (alertElement) alertElement.style.display = 'block';
        
        this.remove();
        
        alert('Обложка будет удалена после сохранения статьи');
    }
});

// Функция загрузки обложки
function uploadFeaturedImage(file) {
    const formData = new FormData();
    formData.append('file', file);
    
    const btn = document.getElementById('uploadFeaturedImageBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Загрузка...';
    btn.disabled = true;
    
    fetch('{{ route("admin.posts.upload-image") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.sizes && data.sizes.large) {
            const imageUrl = data.sizes.large.url;
            
            // Обновляем скрытое поле и URL
            document.getElementById('featured_image_url').value = imageUrl;
            
            // Обновляем превью
            const preview = document.getElementById('featuredImagePreview');
            const img = preview.querySelector('img');
            img.src = imageUrl;
            preview.style.display = 'block';
            
            // Скрываем алерт
            const alertElement = document.getElementById('noFeaturedImageAlert');
            if (alertElement) alertElement.style.display = 'none';
            
            // Добавляем кнопку удаления если её нет
            if (!document.getElementById('removeFeaturedImageBtn')) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-outline-danger';
                removeBtn.id = 'removeFeaturedImageBtn';
                removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
                removeBtn.addEventListener('click', function() {
                    if (confirm('Удалить обложку поста?')) {
                        document.getElementById('featured_image_id').value = '';
                        document.getElementById('featured_image_url').value = '';
                        preview.style.display = 'none';
                        if (alertElement) alertElement.style.display = 'block';
                        this.remove();
                        alert('Обложка будет удалена после сохранения статьи');
                    }
                });
                btn.parentElement.appendChild(removeBtn);
            }
            
            alert('Обложка загружена! Не забудьте сохранить статью.');
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        alert('Ошибка загрузки обложки');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Загрузка SEO изображения (OG:Image)
document.getElementById('uploadSeoImageBtn').addEventListener('click', function() {
    document.getElementById('seoImageInput').click();
});

document.getElementById('seoImageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        uploadSeoImage(file, 'og_image');
    }
});

// Загрузка Twitter изображения
document.getElementById('uploadTwitterImageBtn').addEventListener('click', function() {
    document.getElementById('twitterImageInput').click();
});

document.getElementById('twitterImageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        uploadSeoImage(file, 'twitter_image');
    }
});

// Функция загрузки SEO изображения
function uploadSeoImage(file, targetField) {
    const formData = new FormData();
    formData.append('file', file);
    
    // Показываем индикатор загрузки
    const btn = targetField === 'og_image' 
        ? document.getElementById('uploadSeoImageBtn')
        : document.getElementById('uploadTwitterImageBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Загрузка...';
    btn.disabled = true;
    
    fetch('{{ route("admin.posts.upload-image") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.sizes && data.sizes.large) {
            // Используем large размер для SEO
            const imageUrl = data.sizes.large.url;
            document.getElementById(targetField).value = imageUrl;
            
            // Обновляем превью, если оно есть
            const preview = document.querySelector(`#${targetField}`).closest('.mb-3').querySelector('img');
            if (preview) {
                preview.src = imageUrl;
            } else {
                // Создаем превью если его нет
                const previewDiv = document.createElement('div');
                previewDiv.className = 'mb-2';
                previewDiv.innerHTML = `<img src="${imageUrl}" alt="SEO Image" style="max-width: 300px; border: 1px solid #ddd; border-radius: 4px;">`;
                document.getElementById(targetField).closest('.input-group').parentNode.insertBefore(
                    previewDiv, 
                    document.getElementById(targetField).closest('.input-group')
                );
            }
            
            alert('Изображение успешно загружено!');
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        alert('Ошибка загрузки изображения');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
</script>
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
<!-- TinyMCE CDN -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script src="/vendor/laravel-filemanager/js/stand-alone-button.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация TinyMCE
    tinymce.init({
        selector: '.tinymce-editor',
        // language: 'ru', // Отключено - языковой файл недоступен на CDN
        height: 600,
        width: '100%',
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
        image_title: true,
        image_description: true,
        image_class_list: [
            {title: 'Без класса', value: ''},
            {title: 'По центру', value: 'aligncenter'},
            {title: 'Слева', value: 'alignleft'},
            {title: 'Справа', value: 'alignright'}
        ],
        
        // Обязательные поля для изображения
        a11y_advanced_options: true,
        
        // Интеграция с Laravel FileManager
        file_picker_callback: function(callback, value, meta) {
            let x = window.innerWidth || document.documentElement.clientWidth || document.getElementsByTagName('body')[0].clientWidth;
            let y = window.innerHeight|| document.documentElement.clientHeight|| document.getElementsByTagName('body')[0].clientHeight;

            // Создаем уникальное имя поля для этого вызова
            let fieldId = 'tinymce-file-picker-' + Date.now();
            
            let cmsURL = '/notaadmin/filemanager?editor=tinymce&type=' + meta.filetype + '&field_name=' + fieldId;

            // Создаем скрытое поле, которое будет использовать файловый менеджер
            if (!document.getElementById(fieldId)) {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.id = fieldId;
                input.name = fieldId;
                input.setAttribute('data-tinymce-callback', 'true');
                document.body.appendChild(input);
                
                // Используем MutationObserver для отслеживания изменений значения
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                            const url = input.value || input.getAttribute('value');
                            if (url) {
                                console.log('FileManager вернул URL через attribute:', url);
                                callback(url, { text: url.split('/').pop() });
                                observer.disconnect();
                                setTimeout(() => input.remove(), 100);
                            }
                        }
                    });
                });
                
                observer.observe(input, {
                    attributes: true,
                    attributeFilter: ['value']
                });
                
                // Дополнительно отслеживаем изменения через input/change события
                input.addEventListener('input', function() {
                    const url = this.value;
                    if (url) {
                        console.log('FileManager вернул URL через input event:', url);
                        callback(url, { text: url.split('/').pop() });
                        observer.disconnect();
                        setTimeout(() => this.remove(), 100);
                    }
                });
                
                input.addEventListener('change', function() {
                    const url = this.value;
                    if (url) {
                        console.log('FileManager вернул URL через change event:', url);
                        callback(url, { text: url.split('/').pop() });
                        observer.disconnect();
                        setTimeout(() => this.remove(), 100);
                    }
                });
                
                // Проверяем значение каждые 100ms (резервный метод)
                let checkCount = 0;
                const checkInterval = setInterval(() => {
                    const url = input.value || input.getAttribute('value');
                    if (url) {
                        console.log('FileManager вернул URL через polling:', url);
                        callback(url, { text: url.split('/').pop() });
                        observer.disconnect();
                        clearInterval(checkInterval);
                        setTimeout(() => input.remove(), 100);
                    }
                    checkCount++;
                    if (checkCount > 100) { // 10 секунд максимум
                        clearInterval(checkInterval);
                        observer.disconnect();
                        input.remove();
                    }
                }, 100);
            }

            tinymce.activeEditor.windowManager.openUrl({
                url : cmsURL,
                title : 'Медиатека',
                width : x * 0.8,
                height : y * 0.8,
                onMessage: (api, message) => {
                    // Поддержка postMessage API для современных файловых менеджеров
                    console.log('Получено сообщение через postMessage:', message);
                    if (message.mceAction === 'insertContent' || message.content) {
                        callback(message.content, { text: message.text || message.content.split('/').pop() });
                        api.close();
                    }
                },
                onClose: () => {
                    console.log('Окно файлового менеджера закрыто');
                }
            });
        },
        
        // Автоматическая загрузка изображений (drag & drop, paste)
        automatic_uploads: true,
        images_upload_url: '{{ route("admin.posts.upload-image") }}',
        images_upload_handler: function (blobInfo, progress) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.withCredentials = false;
                xhr.open('POST', '{{ route("admin.posts.upload-image") }}');
                
                xhr.upload.onprogress = (e) => {
                    progress(e.loaded / e.total * 100);
                };
                
                xhr.onload = () => {
                    if (xhr.status === 403) {
                        reject({ message: 'HTTP Error: ' + xhr.status, remove: true });
                        return;
                    }
                    
                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject('HTTP Error: ' + xhr.status);
                        return;
                    }
                    
                    const json = JSON.parse(xhr.responseText);
                    
                    if (!json || typeof json.location != 'string') {
                        reject('Invalid JSON: ' + xhr.responseText);
                        return;
                    }
                    
                    resolve(json.location);
                };
                
                xhr.onerror = () => {
                    reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
                };
                
                const formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                formData.append('_token', '{{ csrf_token() }}');
                
                xhr.send(formData);
            });
        },
        
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
    
    // ===============================================
    // АВТОЗАПОЛНЕНИЕ СВЯЗАННЫХ SEO ПОЛЕЙ
    // ===============================================
    
    // Настройки автозаполнения (какие поля связаны)
    const seoFieldMappings = {
        // При изменении seo_title автоматически заполняются og_title и twitter_title
        'seo_title': ['og_title', 'twitter_title'],
        // При изменении seo_description автоматически заполняются og_description и twitter_description
        'seo_description': ['og_description', 'twitter_description'],
        // При изменении og_image автоматически заполняется twitter_image
        'og_image': ['twitter_image'],
    };
    
    // Флаги для отслеживания, редактировал ли пользователь поле вручную
    const manuallyEdited = {};
    
    // Инициализация автозаполнения
    Object.keys(seoFieldMappings).forEach(sourceField => {
        const sourceInput = document.getElementById(sourceField);
        if (!sourceInput) return;
        
        const targetFields = seoFieldMappings[sourceField];
        
        // При изменении источника - обновляем связанные поля (если они не были отредактированы вручную)
        sourceInput.addEventListener('input', function() {
            targetFields.forEach(targetField => {
                const targetInput = document.getElementById(targetField);
                if (!targetInput) return;
                
                // Автозаполняем только если поле пустое или не было отредактировано вручную
                if (!manuallyEdited[targetField] || targetInput.value === '') {
                    targetInput.value = this.value;
                }
            });
        });
        
        // Отслеживаем ручное редактирование целевых полей
        targetFields.forEach(targetField => {
            const targetInput = document.getElementById(targetField);
            if (!targetInput) return;
            
            targetInput.addEventListener('input', function() {
                // Помечаем как отредактированное вручную
                if (this.value !== document.getElementById(sourceField)?.value) {
                    manuallyEdited[targetField] = true;
                }
            });
            
            // При фокусе - если поле пустое, копируем из источника
            targetInput.addEventListener('focus', function() {
                if (this.value === '') {
                    const sourceInput = document.getElementById(sourceField);
                    if (sourceInput && sourceInput.value) {
                        this.value = sourceInput.value;
                    }
                }
            });
        });
    });
    
    // Код кнопок SEO вынесен в отдельный скрипт ниже
});
</script>

<!-- Отдельный скрипт для SEO генерации (независимый от TinyMCE) -->
<script>
(function() {
    // Ждём полной загрузки страницы
    function initSeoButtons() {
        console.log('Инициализация SEO кнопок...');
        
        // Кнопка генерации SEO
        const generateBtn = document.getElementById('generateSeoBtn');
        if (generateBtn) {
            console.log('Кнопка generateSeoBtn найдена');
            generateBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Кнопка нажата!');
                
                const btn = this;
                const originalText = btn.innerHTML;
                
                // Получаем заголовок
                const titleInput = document.getElementById('post_title');
                const title = titleInput ? titleInput.value.trim() : '';
                
                // Получаем excerpt (краткое описание)
                const excerptInput = document.getElementById('post_excerpt');
                const excerpt = excerptInput ? excerptInput.value.trim() : '';
                
                // Получаем контент - сначала из TinyMCE, потом из textarea
                let content = '';
                try {
                    if (typeof tinymce !== 'undefined' && tinymce.get('post_content')) {
                        content = tinymce.get('post_content').getContent();
                        console.log('Контент получен из TinyMCE');
                    }
                } catch (e) {
                    console.warn('TinyMCE недоступен:', e);
                }
                
                if (!content) {
                    const contentInput = document.getElementById('post_content');
                    content = contentInput ? contentInput.value : '';
                    console.log('Контент получен из textarea');
                }
                
                if (!title) {
                    alert('Заполните название статьи перед генерацией SEO');
                    return;
                }
                
                if (!excerpt && !content) {
                    alert('Заполните краткое описание (excerpt) или содержание статьи перед генерацией SEO');
                    return;
                }
                
                console.log('Данные для генерации:', {
                    title: title.substring(0, 50),
                    excerpt: excerpt ? excerpt.substring(0, 50) : '(пусто)',
                    contentLength: content ? content.length : 0
                });
                
                console.log('Отправка запроса на генерацию SEO...');
                
                // Показываем индикатор загрузки
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Генерация...';
                btn.disabled = true;
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                
                fetch('/notaadmin/posts/generate-seo', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        title: title,
                        excerpt: excerpt,
                        content: content
                    })
                })
                .then(response => {
                    console.log('Ответ получен:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Данные:', data);
                    if (data.success) {
                        const seoData = data.data;
                        console.log('SEO данные для заполнения:', seoData);
                        
                        // Заполняем поля
                        const fields = {
                            'seo_title': seoData.seo_title,
                            'seo_description': seoData.seo_description,
                            'focus_keyword': seoData.focus_keyword,
                            'seo_keywords': seoData.seo_keywords,
                            'og_title': seoData.og_title,
                            'og_description': seoData.og_description,
                            'og_image': seoData.og_image,
                            'twitter_title': seoData.twitter_title,
                            'twitter_description': seoData.twitter_description,
                            'twitter_image': seoData.twitter_image
                        };
                        
                        // Сначала проверим, какие поля вообще есть в DOM
                        console.log('=== Проверка полей в DOM ===');
                        const allSeoFields = ['seo_title', 'seo_description', 'focus_keyword', 'seo_keywords', 
                                             'og_title', 'og_description', 'og_image',
                                             'twitter_title', 'twitter_description', 'twitter_image'];
                        allSeoFields.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            console.log(`Поле ${fieldId}:`, field ? '✅ найдено' : '❌ НЕ НАЙДЕНО');
                        });
                        
                        let filledCount = 0;
                        for (const [fieldId, value] of Object.entries(fields)) {
                            const field = document.getElementById(fieldId);
                            if (field) {
                                if (value && value.trim() !== '') {
                                    const oldValue = field.value;
                                    field.value = value;
                                    filledCount++;
                                    
                                    // Визуальная подсветка заполненного поля
                                    field.style.backgroundColor = '#d4edda';
                                    field.style.borderColor = '#28a745';
                                    setTimeout(() => {
                                        field.style.backgroundColor = '';
                                        field.style.borderColor = '';
                                    }, 2000);
                                    
                                    console.log(`✅ Заполнено поле ${fieldId}:`, {
                                        старое: oldValue,
                                        новое: value.substring(0, 50) + (value.length > 50 ? '...' : ''),
                                        длина: value.length
                                    });
                                    
                                    // Триггерим событие input для обновления счётчиков
                                    field.dispatchEvent(new Event('input', { bubbles: true }));
                                    field.dispatchEvent(new Event('change', { bubbles: true }));
                                } else {
                                    console.warn(`⚠️ Пустое значение для поля ${fieldId}:`, value);
                                }
                            } else {
                                console.error(`❌ Поле ${fieldId} не найдено в DOM!`);
                            }
                        }
                        
                        console.log(`Заполнено полей: ${filledCount}`);
                        
                        // Обновляем счётчики
                        const titleLength = document.getElementById('seo_title_length');
                        if (titleLength && seoData.seo_title) {
                            titleLength.textContent = seoData.seo_title.length;
                            console.log('Обновлён счётчик seo_title_length:', seoData.seo_title.length);
                        }
                        const descLength = document.getElementById('seo_description_length');
                        if (descLength && seoData.seo_description) {
                            descLength.textContent = seoData.seo_description.length;
                            console.log('Обновлён счётчик seo_description_length:', seoData.seo_description.length);
                        }
                        
                        if (filledCount > 0) {
                            alert('✅ SEO-данные успешно сгенерированы! Заполнено полей: ' + filledCount + '. Не забудьте сохранить изменения.');
                        } else {
                            alert('⚠️ Данные получены, но поля не заполнены. Проверьте консоль для деталей.');
                        }
                    } else {
                        alert('❌ Ошибка: ' + (data.message || 'Неизвестная ошибка'));
                    }
                })
                .catch(error => {
                    console.error('SEO Generation error:', error);
                    alert('❌ Ошибка генерации SEO: ' + error.message);
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            });
        } else {
            console.warn('Кнопка generateSeoBtn не найдена');
        }
        
        // Кнопка синхронизации
        const syncBtn = document.getElementById('syncSeoFieldsBtn');
        if (syncBtn) {
            syncBtn.addEventListener('click', function() {
                // Синхронизируем title поля
                const seoTitle = document.getElementById('seo_title');
                const ogTitle = document.getElementById('og_title');
                const twitterTitle = document.getElementById('twitter_title');
                
                if (seoTitle && seoTitle.value) {
                    if (ogTitle) ogTitle.value = seoTitle.value;
                    if (twitterTitle) twitterTitle.value = seoTitle.value;
                }
                
                // Синхронизируем description поля
                const seoDesc = document.getElementById('seo_description');
                const ogDesc = document.getElementById('og_description');
                const twitterDesc = document.getElementById('twitter_description');
                
                if (seoDesc && seoDesc.value) {
                    if (ogDesc) ogDesc.value = seoDesc.value;
                    if (twitterDesc) twitterDesc.value = seoDesc.value;
                }
                
                // Синхронизируем image поля
                const ogImage = document.getElementById('og_image');
                const twitterImage = document.getElementById('twitter_image');
                
                if (ogImage && ogImage.value) {
                    if (twitterImage) twitterImage.value = ogImage.value;
                }
                
                alert('✅ SEO-поля синхронизированы!');
            });
        }
    }
        
        // Кнопка рерайта текста
        const rewriteBtn = document.getElementById('rewriteContentBtn');
        if (rewriteBtn) {
            console.log('Кнопка rewriteContentBtn найдена');
            rewriteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Кнопка рерайта нажата!');
                
                const btn = this;
                const originalText = btn.innerHTML;
                
                // Получаем текущий контент (с HTML разметкой)
                let content = '';
                try {
                    if (typeof tinymce !== 'undefined' && tinymce.get('post_content')) {
                        content = tinymce.get('post_content').getContent();
                        console.log('Контент получен из TinyMCE с HTML');
                    }
                } catch (e) {
                    console.warn('TinyMCE недоступен:', e);
                }
                
                if (!content) {
                    const contentInput = document.getElementById('post_content');
                    content = contentInput ? contentInput.value : '';
                }
                
                if (!content || content.trim().length < 50) {
                    alert('Текст статьи слишком короткий для рерайта. Минимум 50 символов.');
                    return;
                }
                
                // Получаем заголовок для контекста
                const titleInput = document.getElementById('post_title');
                const title = titleInput ? titleInput.value.trim() : '';
                
                // Подтверждение
                if (!confirm('Вы уверены, что хотите переписать текст статьи? Текущий текст будет заменен.')) {
                    return;
                }
                
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Рерайт...';
                btn.disabled = true;
                
                // Отправляем запрос на сервер
                fetch('{{ route("admin.posts.rewrite-content") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        content: content,
                        title: title,
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.rewritten_content) {
                        // Вставляем переписанный текст в редактор
                        try {
                            if (typeof tinymce !== 'undefined' && tinymce.get('post_content')) {
                                tinymce.get('post_content').setContent(data.rewritten_content);
                                console.log('Текст вставлен в TinyMCE');
                            } else {
                                const contentInput = document.getElementById('post_content');
                                if (contentInput) {
                                    contentInput.value = data.rewritten_content;
                                    console.log('Текст вставлен в textarea');
                                }
                            }
                            alert('✅ Текст успешно переписан!');
                        } catch (e) {
                            console.error('Ошибка вставки текста:', e);
                            alert('Текст переписан, но не удалось вставить в редактор. Скопируйте из консоли.');
                            console.log('Переписанный текст:', data.rewritten_content);
                        }
                    } else {
                        alert('❌ Ошибка: ' + (data.message || 'Не удалось переписать текст'));
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert('❌ Ошибка при отправке запроса: ' + error.message);
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            });
        } else {
            console.warn('Кнопка rewriteContentBtn не найдена');
        }

    
    // Инициализируем после загрузки DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSeoButtons);
    } else {
        initSeoButtons();
    }
})();
</script>
@endpush

