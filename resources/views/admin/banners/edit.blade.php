@extends('layouts.admin')

@section('title', 'Редактировать баннер')

@section('content')
<style>
    .card.banner-form {
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,.05);
    }
    .preview-area {
        min-height: 200px;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background: #fff;
    }
    .preview-area img {
        max-width: 100%;
        height: auto;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-9 col-lg-10 mx-auto">
            <div class="card banner-form mb-4">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Редактировать баннер</h4>
                        <p class="text-muted mb-0">ID: {{ $banner->id }}</p>
                    </div>
                    <a href="{{ route('admin.banners') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>К списку
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong><i class="fas fa-exclamation-triangle me-1"></i>Ошибки:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.banners.update', $banner->id) }}" method="POST">
                        @csrf
                        @method('POST')

                        <div class="mb-3">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading me-1"></i>Название баннера *
                            </label>
                            <input type="text" class="form-control" id="title" name="title"
                                   value="{{ old('title', $banner->title) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">
                                <i class="fas fa-tag me-1"></i>Тип баннера *
                            </label>
                            <select class="form-select" id="type" name="type" required onchange="toggleContentFields()">
                                <option value="image" {{ old('type', $banner->type) === 'image' ? 'selected' : '' }}>Изображение</option>
                                <option value="html" {{ old('type', $banner->type) === 'html' ? 'selected' : '' }}>HTML код</option>
                                <option value="js" {{ old('type', $banner->type) === 'js' ? 'selected' : '' }}>JavaScript</option>
                            </select>
                        </div>

                        <div class="mb-3 {{ old('type', $banner->type) !== 'image' ? 'd-none' : '' }}" id="imageField">
                            <label for="content" class="form-label">
                                <i class="fas fa-image me-1"></i>URL изображения *
                            </label>
                            <input type="text" class="form-control" id="content" name="content"
                                   value="{{ old('content', $banner->type === 'image' ? $banner->content : '') }}"
                                   {{ old('type', $banner->type) === 'image' ? 'required' : '' }}
                                   placeholder="https://example.com/banner.jpg" onchange="updatePreview()">
                        </div>

                        <div class="mb-3 {{ old('type', $banner->type) === 'image' ? 'd-none' : '' }}" id="codeField">
                            <label for="content_code" class="form-label">
                                <i class="fas fa-code me-1"></i>Код *
                            </label>
                            <textarea class="form-control" id="content_code" rows="6"
                                      {{ old('type', $banner->type) === 'image' ? '' : 'required' }}
                                      placeholder="Вставьте HTML или JavaScript код">{{ old('content', $banner->type !== 'image' ? $banner->content : '') }}</textarea>
                        </div>

                        <div class="mb-3 {{ old('type', $banner->type) !== 'image' ? 'd-none' : '' }}" id="previewField">
                            <label class="form-label">
                                <i class="fas fa-eye me-1"></i>Предпросмотр
                            </label>
                            <div class="preview-area" id="bannerPreview">
                                @if(old('type', $banner->type) === 'image' && (old('content', $banner->content)))
                                    <img src="{{ old('content', $banner->content) }}" alt="Preview">
                                @else
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-image fa-3x mb-2"></i><br>Предпросмотр появится после ввода URL
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3" id="linkField">
                            <label for="link_url" class="form-label">
                                <i class="fas fa-link me-1"></i>Ссылка
                            </label>
                            <input type="url" class="form-control" id="link_url" name="link_url"
                                   value="{{ old('link_url', $banner->link_url) }}" placeholder="https://example.com">
                        </div>

                        <div class="mb-3" id="targetField">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="target_blank"
                                       name="target_blank" value="1"
                                       {{ old('target_blank', $banner->target_blank) ? 'checked' : '' }}>
                                <label class="form-check-label" for="target_blank">Открывать в новой вкладке</label>
                            </div>
                        </div>

                        <div class="row mb-3" id="dimensionsField">
                            <div class="col-md-6">
                                <label for="width" class="form-label">
                                    <i class="fas fa-arrows-alt-h me-1"></i>Ширина
                                </label>
                                <input type="number" class="form-control" id="width" name="width"
                                       value="{{ old('width', $banner->width) }}" placeholder="728">
                            </div>
                            <div class="col-md-6">
                                <label for="height" class="form-label">
                                    <i class="fas fa-arrows-alt-v me-1"></i>Высота
                                </label>
                                <input type="number" class="form-control" id="height" name="height"
                                       value="{{ old('height', $banner->height) }}" placeholder="90">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="zone" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Зона размещения *
                            </label>
                            <select class="form-select" id="zone" name="zone" required>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone->name }}" {{ old('zone', $banner->zone) === $zone->name ? 'selected' : '' }}>
                                        {{ $zone->display_name }} ({{ $zone->recommended_width }}x{{ $zone->recommended_height }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="priority" class="form-label">
                                <i class="fas fa-sort-amount-up me-1"></i>Приоритет *
                            </label>
                            <select class="form-select" id="priority" name="priority" required>
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ old('priority', $banner->priority) == $i ? 'selected' : '' }}>
                                        {{ $i }} {{ $i === 10 ? '(максимальный)' : ($i === 1 ? '(минимальный)' : '') }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">
                                    <i class="far fa-calendar-alt me-1"></i>Дата начала
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date"
                                       value="{{ old('start_date', optional($banner->start_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">
                                    <i class="far fa-calendar-times me-1"></i>Дата окончания
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date"
                                       value="{{ old('end_date', optional($banner->end_date)->format('Y-m-d')) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">
                                <i class="fas fa-toggle-on me-1"></i>Статус *
                            </label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" {{ old('status', $banner->status) === 'active' ? 'selected' : '' }}>Активен</option>
                                <option value="paused" {{ old('status', $banner->status) === 'paused' ? 'selected' : '' }}>На паузе</option>
                                <option value="expired" {{ old('status', $banner->status) === 'expired' ? 'selected' : '' }}>Истек</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-sitemap me-1"></i>Отображать на страницах
                            </label>
                            <div class="card border-light bg-light">
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="show_on_home"
                                               name="show_on_home" value="1" {{ old('show_on_home', $banner->show_on_home) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_on_home">
                                            <i class="fas fa-home me-1 text-primary"></i><strong>Главная страница</strong>
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="show_on_category"
                                               name="show_on_category" value="1" {{ old('show_on_category', $banner->show_on_category) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_on_category">
                                            <i class="fas fa-folder me-1 text-warning"></i><strong>Страницы категорий</strong>
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="show_on_post"
                                               name="show_on_post" value="1" {{ old('show_on_post', $banner->show_on_post) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_on_post">
                                            <i class="fas fa-file-alt me-1 text-success"></i><strong>Страницы статей</strong>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="show_on_other"
                                               name="show_on_other" value="1" {{ old('show_on_other', $banner->show_on_other) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_on_other">
                                            <i class="fas fa-globe me-1 text-info"></i><strong>Остальные страницы</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted mt-2 d-block">
                                        <i class="fas fa-info-circle me-1"></i>Выберите, на каких типах страниц будет отображаться баннер
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('admin.banners') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Отмена
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Сохранить изменения
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <small class="text-muted text-uppercase">Показов</small>
                            <h3 class="text-primary mb-0">{{ number_format($stats['impressions']) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <small class="text-muted text-uppercase">Кликов</small>
                            <h3 class="text-success mb-0">{{ number_format($stats['clicks']) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <small class="text-muted text-uppercase">CTR</small>
                            <h3 class="text-warning mb-0">{{ $stats['ctr'] }}%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleContentFields() {
        const type = document.getElementById('type').value;
        const imageField = document.getElementById('imageField');
        const codeField = document.getElementById('codeField');
        const previewField = document.getElementById('previewField');
        const linkField = document.getElementById('linkField');
        const targetField = document.getElementById('targetField');
        const dimensionsField = document.getElementById('dimensionsField');

        if (type === 'image') {
            imageField.classList.remove('d-none');
            codeField.classList.add უმ 'd-none';
            previewField.classList.remove('d-none');
            linkField.classList.remove('d-none');
            targetField.classList.remove('d-none');
            dimensionsField.classList.remove('d-none');
            
            document.getElementById('content').required = true;
            document.getElementById('content_code').required = false;
        } else {
            imageField.classList.add('d-none');
            codeField.classList.remove('d-none');
            previewField.classList.add('d-none');
            linkField.classList.add('d-none');
            targetField.classList.add('d-none');
            dimensionsField.classList.add('d-none');
            
            document.getElementById('content').required = false;
            document.getElementById('content_code').required = true;
        }
    }

    function updatePreview() {
        const url = document.getElementById('content').value;
        const preview = document.getElementById('bannerPreview');

        if (url) {
            preview.innerHTML = '<img src="' + url + '" alt="Preview">';
        } else {
            preview.innerHTML = '<p class="text-muted mb-0"><i class="fas fa-image fa-3x mb-2"></i><br>Предпросмотр появится после ввода URL</p>';
        }
    }

    document.querySelector('form').addEventListener('submit', function() {
        const type = document.getElementById('type').value;
        if (type !== 'image') {
            document.getElementById('content').value = document.getElementById('content_code').value;
        }
    });

    toggleContentFields();
</script>
@endpush




