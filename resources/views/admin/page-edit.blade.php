@extends('layouts.admin')
@section('title', 'Редактирование страницы')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-edit"></i> Редактирование страницы</h1>
        <a href="{{ route('admin.pages') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Назад к списку
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.pages.update', $page->ID) }}" method="POST">
                @csrf
                
                <!-- Название -->
                <div class="mb-3">
                    <label for="post_title" class="form-label">Название страницы</label>
                    <input type="text" 
                           class="form-control @error('post_title') is-invalid @enderror" 
                           id="post_title" 
                           name="post_title" 
                           value="{{ old('post_title', $page->post_title) }}" 
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
                           value="{{ $page->post_name }}" 
                           readonly>
                    <small class="text-muted">Постоянная ссылка: {{ route('post', $page->post_name) }}</small>
                </div>
                
                <!-- Контент -->
                <div class="mb-3">
                    <label for="post_content" class="form-label">Содержание</label>
                    <textarea class="form-control @error('post_content') is-invalid @enderror" 
                              id="post_content" 
                              name="post_content" 
                              rows="15" 
                              required>{{ old('post_content', $page->post_content) }}</textarea>
                    @error('post_content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">HTML разрешен. Можете использовать теги для форматирования.</small>
                </div>
                
                <!-- Краткое описание -->
                <div class="mb-3">
                    <label for="post_excerpt" class="form-label">Краткое описание (excerpt)</label>
                    <textarea class="form-control @error('post_excerpt') is-invalid @enderror" 
                              id="post_excerpt" 
                              name="post_excerpt" 
                              rows="3">{{ old('post_excerpt', $page->post_excerpt) }}</textarea>
                    @error('post_excerpt')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Краткое описание страницы для превью</small>
                </div>
                
                <!-- Статус -->
                <div class="mb-3">
                    <label for="post_status" class="form-label">Статус</label>
                    <select class="form-select @error('post_status') is-invalid @enderror" 
                            id="post_status" 
                            name="post_status" 
                            required>
                        <option value="publish" {{ old('post_status', $page->post_status) == 'publish' ? 'selected' : '' }}>Опубликовано</option>
                        <option value="draft" {{ old('post_status', $page->post_status) == 'draft' ? 'selected' : '' }}>Черновик</option>
                        <option value="pending" {{ old('post_status', $page->post_status) == 'pending' ? 'selected' : '' }}>Ожидает проверки</option>
                    </select>
                    @error('post_status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Мета информация -->
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Информация о странице
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>ID:</strong> {{ $page->ID }}</p>
                        <p class="mb-1"><strong>Автор:</strong> {{ $page->user->display_name ?? 'Неизвестен' }}</p>
                        <p class="mb-1"><strong>Дата создания:</strong> {{ \Carbon\Carbon::parse($page->post_date)->format('d.m.Y H:i') }}</p>
                        <p class="mb-1"><strong>Последнее изменение:</strong> {{ \Carbon\Carbon::parse($page->post_modified)->format('d.m.Y H:i') }}</p>
                        <p class="mb-0"><strong>Просмотров:</strong> {{ $page->getMeta('views', 0) }}</p>
                    </div>
                </div>
                
                <!-- Кнопки действий -->
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                    <a href="{{ route('post', $page->post_name) }}" target="_blank" class="btn btn-outline-secondary">
                        <i class="fas fa-external-link-alt"></i> Просмотреть на сайте
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Можно добавить WYSIWYG редактор позже, например TinyMCE
</script>
@endpush




