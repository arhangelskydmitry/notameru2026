@extends('layouts.admin')

@section('title', 'Редактировать счетчик')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Редактировать счетчик</h1>
        <a href="{{ route('admin.counters.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Назад
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.counters.update', $counter) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Название счетчика *</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $counter->name) }}"
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
                                <option value="sidebar" {{ old('position', $counter->position) === 'sidebar' ? 'selected' : '' }}>Сайдбар</option>
                                <option value="footer" {{ old('position', $counter->position) === 'footer' ? 'selected' : '' }}>Футер</option>
                                <option value="header" {{ old('position', $counter->position) === 'header' ? 'selected' : '' }}>Хедер</option>
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
                                   value="{{ old('sort_order', $counter->sort_order) }}"
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
                                      required>{{ old('code', $counter->code) }}</textarea>
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
                                       {{ old('is_active', $counter->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Активен
                                </label>
                            </div>
                            <div class="form-text">Показывать счетчик на сайте</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить изменения
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
                    <i class="fas fa-info-circle"></i> Информация
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> {{ $counter->id }}</p>
                    <p><strong>Создан:</strong> {{ $counter->created_at->format('d.m.Y H:i') }}</p>
                    <p><strong>Обновлен:</strong> {{ $counter->updated_at->format('d.m.Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
