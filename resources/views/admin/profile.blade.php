@extends('layouts.admin')
@section('title', 'Мой профиль')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-user-circle"></i> Мой профиль</h2>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Редактирование профиля</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.profile.update') }}" method="POST">
                    @csrf
                    
                    <!-- Отображаемое имя -->
                    <div class="mb-3">
                        <label for="display_name" class="form-label">Отображаемое имя *</label>
                        <input type="text" 
                               class="form-control @error('display_name') is-invalid @enderror" 
                               id="display_name" 
                               name="display_name" 
                               value="{{ old('display_name', $user->display_name) }}" 
                               required>
                        @error('display_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Это имя будет отображаться на сайте и в админ-панели</small>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="user_email" class="form-label">Email *</label>
                        <input type="email" 
                               class="form-control @error('user_email') is-invalid @enderror" 
                               id="user_email" 
                               name="user_email" 
                               value="{{ old('user_email', $user->user_email) }}" 
                               required>
                        @error('user_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-4">

                    <!-- Смена пароля -->
                    <h5 class="mb-3"><i class="fas fa-key"></i> Смена пароля</h5>
                    <p class="text-muted">Оставьте поля пустыми, если не хотите менять пароль</p>

                    <div class="mb-3">
                        <label for="password" class="form-label">Новый пароль</label>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               minlength="8">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Минимум 8 символов</small>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Подтверждение пароля</label>
                        <input type="password" 
                               class="form-control" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               minlength="8">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Информация о пользователе -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-id-card"></i> Информация</h5>
            </div>
            <div class="card-body">
                <p><strong>Логин:</strong><br>{{ $user->user_login }}</p>
                
                @if($user->getRole())
                    <p><strong>Роль:</strong><br>
                        <span class="badge bg-{{ $user->isSuperAdmin() ? 'danger' : ($user->isEditor() ? 'warning' : 'info') }}">
                            {{ $user->getRole()->display_name }}
                        </span>
                    </p>
                @endif
                
                @if($user->getPosition())
                    <p><strong>Должность:</strong><br>{{ $user->getPosition() }}</p>
                @endif
                
                <p class="mb-0"><strong>ID пользователя:</strong><br>{{ $user->ID }}</p>
            </div>
        </div>

        <!-- Статистика -->
        @if($user->statistics)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Моя статистика</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <h2 class="mb-0">{{ $user->statistics->total_posts }}</h2>
                        <small class="text-muted">Всего статей</small>
                    </div>
                    <div class="mb-3">
                        <h3 class="mb-0 text-success">{{ $user->statistics->published_posts }}</h3>
                        <small class="text-muted">Опубликовано</small>
                    </div>
                    <div class="mb-3">
                        <h3 class="mb-0 text-info">{{ number_format($user->statistics->total_views) }}</h3>
                        <small class="text-muted">Просмотров</small>
                    </div>
                    <a href="{{ route('admin.my-statistics') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-chart-line"></i> Подробная статистика
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

