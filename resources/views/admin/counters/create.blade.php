@extends('layouts.admin')

@section('title', 'Создать счетчик')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Создать новый счетчик</h1>
        <a href="{{ route('admin.counters.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Назад
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.counters.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Название счетчика *</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}"
                                   placeholder="Например: Яндекс Метрика, Google Analytics"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Название для удобства в админке (не отображается на сайте)</div>
                        </div>

                        <div class="mb-3">
                            <label for="position" class="form-label">Позиция *</label>
                            <select class="form-select @error('position') is-invalid @enderror" 
                                    id="position" 
                                    name="position" 
                                    required>
                                <option value="sidebar" {{ old('position') === 'sidebar' ? 'selected' : '' }}>Сайдбар</option>
                                <option value="footer" {{ old('position') === 'footer' ? 'selected' : '' }}>Футер</option>
                                <option value="header" {{ old('position') === 'header' ? 'selected' : '' }}>Хедер</option>
                            </select>
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Где показывать счетчик</div>
                        </div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Порядок сортировки</label>
                            <input type="number" 
                                   class="form-control @error('sort_order') is-invalid @enderror" 
                                   id="sort_order" 
                                   name="sort_order" 
                                   value="{{ old('sort_order', 0) }}"
                                   min="0">
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Меньшее число = выше в списке</div>
                        </div>

                        <div class="mb-3">
                            <label for="code" class="form-label">HTML код счетчика *</label>
                            <textarea class="form-control @error('code') is-invalid @enderror" 
                                      id="code" 
                                      name="code" 
                                      rows="10"
                                      placeholder="Вставьте сюда код из личного кабинета Яндекс Метрики, Google Analytics и т.д."
                                      required>{{ old('code') }}</textarea>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Скопируйте код из личного кабинета системы аналитики</div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active"
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Активен
                                </label>
                            </div>
                            <div class="form-text">Показывать счетчик на сайте</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить
                            </button>
                            <a href="{{ route('admin.counters.index') }}" class="btn btn-outline-secondary">
                                Отмена
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-lightbulb"></i> Подсказки
                </div>
                <div class="card-body">
                    <h6>Где взять код счетчика?</h6>
                    
                    <p><strong>Яндекс Метрика:</strong></p>
                    <ol class="small">
                        <li>Перейдите в <a href="https://metrika.yandex.ru" target="_blank">metrika.yandex.ru</a></li>
                        <li>Выберите счетчик</li>
                        <li>Настройки → Код счетчика</li>
                        <li>Скопируйте весь код</li>
                    </ol>

                    <p><strong>Google Analytics:</strong></p>
                    <ol class="small">
                        <li>Перейдите в <a href="https://analytics.google.com" target="_blank">analytics.google.com</a></li>
                        <li>Администратор → Информация об отслеживании</li>
                        <li>Код отслеживания</li>
                        <li>Скопируйте Global Site Tag</li>
                    </ol>

                    <hr>

                    <h6>Позиции:</h6>
                    <ul class="small">
                        <li><strong>Сайдбар:</strong> Правая колонка</li>
                        <li><strong>Футер:</strong> Низ страницы</li>
                        <li><strong>Хедер:</strong> Вверх страницы</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
