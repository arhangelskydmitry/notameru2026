<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать баннер - Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
        }
        .form-label {
            font-weight: 600;
            color: #495057;
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
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                <i class="fas fa-tachometer-alt"></i> Админ-панель
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('admin.banners') }}">
                    <i class="fas fa-arrow-left"></i> К списку баннеров
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-plus-circle"></i> Создать новый баннер</h4>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong><i class="fas fa-exclamation-triangle"></i> Ошибки:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <form action="{{ route('admin.banners.store') }}" method="POST">
                            @csrf

                            <!-- Title -->
                            <div class="mb-3">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading"></i> Название баннера *
                                </label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="{{ old('title') }}" required 
                                       placeholder="Например: Баннер в шапке сайта">
                                <small class="text-muted">Название для внутреннего использования</small>
                            </div>

                            <!-- Type -->
                            <div class="mb-3">
                                <label for="type" class="form-label">
                                    <i class="fas fa-tag"></i> Тип баннера *
                                </label>
                                <select class="form-select" id="type" name="type" required onchange="toggleContentFields()">
                                    <option value="image" {{ old('type') === 'image' ? 'selected' : '' }}>Изображение</option>
                                    <option value="html" {{ old('type') === 'html' ? 'selected' : '' }}>HTML код</option>
                                    <option value="js" {{ old('type') === 'js' ? 'selected' : '' }}>JavaScript</option>
                                </select>
                            </div>

                            <!-- Content (Image URL) -->
                            <div class="mb-3" id="imageField">
                                <label for="content" class="form-label">
                                    <i class="fas fa-image"></i> URL изображения *
                                </label>
                                <input type="text" class="form-control" id="content" name="content" 
                                       value="{{ old('content') }}" required 
                                       placeholder="https://example.com/banner.jpg"
                                       onchange="updatePreview()">
                                <small class="text-muted">Прямая ссылка на изображение баннера</small>
                            </div>

                            <!-- Content (HTML/JS) -->
                            <div class="mb-3 d-none" id="codeField">
                                <label for="content_code" class="form-label">
                                    <i class="fas fa-code"></i> Код *
                                </label>
                                <textarea class="form-control" id="content_code" rows="6" 
                                          placeholder="Вставьте HTML или JavaScript код">{{ old('content') }}</textarea>
                                <small class="text-muted">Вставьте готовый код баннера от рекламной сети</small>
                            </div>

                            <!-- Preview -->
                            <div class="mb-3" id="previewField">
                                <label class="form-label">
                                    <i class="fas fa-eye"></i> Предпросмотр
                                </label>
                                <div class="preview-area" id="bannerPreview">
                                    <p class="text-muted"><i class="fas fa-image fa-3x"></i><br>Предпросмотр появится после ввода URL</p>
                                </div>
                            </div>

                            <!-- Link URL -->
                            <div class="mb-3" id="linkField">
                                <label for="link_url" class="form-label">
                                    <i class="fas fa-link"></i> Ссылка (необязательно)
                                </label>
                                <input type="url" class="form-control" id="link_url" name="link_url" 
                                       value="{{ old('link_url') }}" 
                                       placeholder="https://example.com">
                                <small class="text-muted">Куда ведет клик по баннеру</small>
                            </div>

                            <!-- Target Blank -->
                            <div class="mb-3" id="targetField">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="target_blank" 
                                           name="target_blank" value="1" {{ old('target_blank') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="target_blank">
                                        Открывать в новой вкладке
                                    </label>
                                </div>
                            </div>

                            <!-- Dimensions -->
                            <div class="row mb-3" id="dimensionsField">
                                <div class="col-md-6">
                                    <label for="width" class="form-label">
                                        <i class="fas fa-arrows-alt-h"></i> Ширина (px)
                                    </label>
                                    <input type="number" class="form-control" id="width" name="width" 
                                           value="{{ old('width') }}" placeholder="728">
                                </div>
                                <div class="col-md-6">
                                    <label for="height" class="form-label">
                                        <i class="fas fa-arrows-alt-v"></i> Высота (px)
                                    </label>
                                    <input type="number" class="form-control" id="height" name="height" 
                                           value="{{ old('height') }}" placeholder="90">
                                </div>
                            </div>

                            <!-- Zone -->
                            <div class="mb-3">
                                <label for="zone" class="form-label">
                                    <i class="fas fa-map-marker-alt"></i> Зона размещения *
                                </label>
                                <select class="form-select" id="zone" name="zone" required>
                                    @foreach($zones as $zone)
                                    <option value="{{ $zone->name }}" {{ old('zone') === $zone->name ? 'selected' : '' }}>
                                        {{ $zone->display_name }} ({{ $zone->recommended_width }}x{{ $zone->recommended_height }})
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Где будет отображаться баннер</small>
                            </div>

                            <!-- Priority -->
                            <div class="mb-3">
                                <label for="priority" class="form-label">
                                    <i class="fas fa-sort-amount-up"></i> Приоритет *
                                </label>
                                <select class="form-select" id="priority" name="priority" required>
                                    @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ old('priority', 5) == $i ? 'selected' : '' }}>
                                        {{ $i }} {{ $i === 10 ? '(максимальный)' : ($i === 1 ? '(минимальный)' : '') }}
                                    </option>
                                    @endfor
                                </select>
                                <small class="text-muted">При нескольких баннерах в зоне будет выбираться случайный с учетом приоритета</small>
                            </div>

                            <!-- Dates -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label">
                                        <i class="far fa-calendar-alt"></i> Дата начала
                                    </label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="{{ old('start_date') }}">
                                    <small class="text-muted">Оставьте пустым для немедленного показа</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">
                                        <i class="far fa-calendar-times"></i> Дата окончания
                                    </label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="{{ old('end_date') }}">
                                    <small class="text-muted">Оставьте пустым для бессрочного показа</small>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <label for="status" class="form-label">
                                    <i class="fas fa-toggle-on"></i> Статус *
                                </label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Активен</option>
                                    <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>На паузе</option>
                                    <option value="expired" {{ old('status') === 'expired' ? 'selected' : '' }}>Истек</option>
                                </select>
                            </div>

                            <!-- Submit -->
                            <div class="d-flex justify-content-between mt-4">
                                <a href="{{ route('admin.banners') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Отмена
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Создать баннер
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                codeField.classList.add('d-none');
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
                preview.innerHTML = '<img src="' + url + '" alt="Preview" style="max-width: 100%; height: auto;">';
            } else {
                preview.innerHTML = '<p class="text-muted"><i class="fas fa-image fa-3x"></i><br>Предпросмотр появится после ввода URL</p>';
            }
        }

        // При отправке формы копируем content_code в content
        document.querySelector('form').addEventListener('submit', function(e) {
            const type = document.getElementById('type').value;
            if (type !== 'image') {
                document.getElementById('content').value = document.getElementById('content_code').value;
            }
        });

        // Initialize on page load
        toggleContentFields();
    </script>
</body>
</html>




