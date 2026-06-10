@extends('layouts.admin')
@section('title', 'Импорт настроек SEO AI')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-upload"></i> Импорт настроек SEO AI</h1>
        <div>
            <a href="{{ route('admin.seo-settings') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Назад к настройкам
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> 
            <pre style="white-space: pre-wrap; margin: 0;">{{ session('success') }}</pre>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-file-upload"></i> Загрузка файла настроек
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.seo-settings.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="settings_file" class="form-label fw-bold">
                                <i class="fas fa-file-code"></i> JSON файл с настройками
                            </label>
                            <input type="file" 
                                   class="form-control @error('settings_file') is-invalid @enderror" 
                                   id="settings_file" 
                                   name="settings_file" 
                                   accept=".json,.txt"
                                   required>
                            @error('settings_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Загрузите файл seo-settings.json, полученный после экспорта настроек
                            </small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Инструкция:</strong>
                            <ol class="mb-0 mt-2">
                                <li>На локальном сервере выполните: <code>php scripts/export-seo-settings.php > seo-settings.json</code></li>
                                <li>Загрузите полученный файл через форму выше</li>
                                <li>После импорта добавьте OPENAI_API_KEY в .env файл на сервере</li>
                                <li>Очистите кеш: <code>php artisan config:clear && php artisan cache:clear</code></li>
                            </ol>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Импортировать настройки
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Что будет импортировано
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><strong>seo_ai_provider</strong> - Предпочтительный провайдер (gigachat/openai/chatinfo)</li>
                        <li><strong>gigachat_client_id</strong> - Client ID для GigaChat</li>
                        <li><strong>gigachat_client_secret</strong> - Client Secret для GigaChat</li>
                        <li><strong>gigachat_scope</strong> - Scope для GigaChat</li>
                        <li><strong>chatinfo_api_key</strong> - API ключ для ChatInfo</li>
                        <li><strong>openai_api_key</strong> - API ключ для OpenAI (показывается в сообщении, нужно добавить в .env)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
