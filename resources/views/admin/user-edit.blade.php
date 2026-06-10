@extends('layouts.admin')
@section('title', 'Редактирование пользователя')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-user-edit"></i> Редактирование пользователя: {{ $user->display_name }}</h2>
    <a href="{{ route('admin.users') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Назад к списку
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.users.update', $user->ID) }}" method="POST">
                    @csrf
                    
                    <!-- Имя -->
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
                    </div>

                    <!-- Логин -->
                    <div class="mb-3">
                        <label for="user_login" class="form-label">Логин *</label>
                        <input type="text"
                               class="form-control @error('user_login') is-invalid @enderror"
                               id="user_login"
                               name="user_login"
                               value="{{ old('user_login', $user->user_login) }}"
                               required>
                        @error('user_login')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Используется для входа. Допускаются буквы, цифры, точки, дефисы и нижние подчеркивания. Минимум 3 символа.</small>
                    </div>

                    <!-- Slug автора -->
                    <div class="mb-3">
                        <label for="user_nicename" class="form-label">Slug автора *</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ url('/author') }}/</span>
                            <input type="text"
                                   class="form-control @error('user_nicename') is-invalid @enderror"
                                   id="user_nicename"
                                   name="user_nicename"
                                   value="{{ old('user_nicename', $user->user_nicename) }}"
                                   required>
                        </div>
                        @error('user_nicename')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Используется в URL страницы автора. Допускаются только латинские буквы, цифры и дефисы.</small>
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

                    <!-- Роль -->
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Роль *</label>
                        <select class="form-select @error('role_id') is-invalid @enderror" 
                                id="role_id" 
                                name="role_id" 
                                required>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" 
                                        {{ old('role_id', $user->userRole?->role_id) == $role->id ? 'selected' : '' }}>
                                    {{ $role->display_name }} (уровень {{ $role->level }})
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Выберите роль для пользователя</small>
                    </div>

                    <!-- Должность -->
                    <div class="mb-3">
                        <label for="position" class="form-label">Должность</label>
                        <input type="text" 
                               class="form-control @error('position') is-invalid @enderror" 
                               id="position" 
                               name="position" 
                               value="{{ old('position', $user->userRole?->position) }}" 
                               placeholder="Например: Журналист, Корреспондент">
                        @error('position')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Разрешенные категории -->
                    <div class="mb-3">
                        <label class="form-label">Разрешенные категории для редактирования</label>
                        <small class="text-muted d-block mb-2">Если не выбрано ни одной категории, автор может работать со всеми категориями</small>
                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            @foreach($categories as $category)
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="allowed_categories[]" 
                                           value="{{ $category->term_taxonomy_id }}" 
                                           id="cat_{{ $category->term_taxonomy_id }}"
                                           {{ in_array($category->term_taxonomy_id, old('allowed_categories', $user->userRole?->allowed_categories ?? [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cat_{{ $category->term_taxonomy_id }}">
                                        {{ $category->term->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
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
        <!-- Статистика пользователя -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Статистика</h5>
            </div>
            <div class="card-body">
                @if($user->statistics)
                    <div class="mb-3">
                        <small class="text-muted">Всего статей</small>
                        <h3 class="mb-0">{{ $user->statistics->total_posts }}</h3>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Опубликовано</small>
                        <h4 class="mb-0 text-success">{{ $user->statistics->published_posts }}</h4>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Черновиков</small>
                        <h4 class="mb-0 text-warning">{{ $user->statistics->draft_posts }}</h4>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Просмотров</small>
                        <h4 class="mb-0 text-info">{{ number_format($user->statistics->total_views) }}</h4>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">За этот месяц</small>
                        <h4 class="mb-0">{{ $user->statistics->this_month_posts }}</h4>
                    </div>
                @else
                    <p class="text-muted mb-0">Статистика отсутствует</p>
                @endif
            </div>
        </div>

        <!-- Пресс-карта -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-id-card"></i> Пресс-карта</h5>
                @if(isset($currentAdminUser) && ($currentAdminUser->isSuperAdmin() || $currentAdminUser->isEditor()))
                    <a href="{{ route('admin.press-cards.create', ['user_id' => $user->ID]) }}" class="btn btn-sm btn-primary">
                        Выдать
                    </a>
                @endif
            </div>
            <div class="card-body">
                @php $activeCard = $user->pressCards->where('status', 'active')->sortByDesc('issued_at')->first(); @endphp
                @if($activeCard)
                    <p class="mb-1"><strong>{{ $activeCard->card_number }}</strong></p>
                    <p class="mb-2 small text-muted">до {{ $activeCard->expires_at->format('d.m.Y') }}</p>
                    <a href="{{ route('admin.press-cards.show', $activeCard->id) }}" class="btn btn-sm btn-outline-secondary">Открыть</a>
                @elseif($user->pressCards->count())
                    <p class="text-muted mb-2">Активной карты нет</p>
                    <a href="{{ route('admin.press-cards.index') }}" class="btn btn-sm btn-outline-secondary">Все карты</a>
                @else
                    <p class="text-muted mb-0">Не выдана</p>
                @endif
            </div>
        </div>

        <!-- Информация о пользователе -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Информация</h5>
            </div>
            <div class="card-body">
                <p><strong>Логин:</strong> {{ $user->user_login }}</p>
                <p><strong>ID:</strong> {{ $user->ID }}</p>
                <p class="mb-0"><strong>Текущая роль:</strong> 
                    @if($user->getRole())
                        <span class="badge bg-{{ $user->isSuperAdmin() ? 'danger' : ($user->isEditor() ? 'warning' : 'info') }}">
                            {{ $user->getRole()->display_name }}
                        </span>
                    @else
                        <span class="badge bg-secondary">Нет роли</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>

@endsection

