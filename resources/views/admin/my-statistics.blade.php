@extends('layouts.admin')
@section('title', 'Моя статистика')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-chart-line"></i> Моя статистика</h2>
</div>

<div class="row">
    <!-- Основная статистика -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Обзор</h4>
            </div>
            <div class="card-body">
                @if($statistics)
                    <div class="row text-center mb-4">
                        <div class="col-md-3">
                            <div class="p-3">
                                <h2 class="mb-0">{{ $statistics->total_posts }}</h2>
                                <p class="text-muted mb-0">Всего статей</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h2 class="mb-0 text-success">{{ $statistics->published_posts }}</h2>
                                <p class="text-muted mb-0">Опубликовано</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h2 class="mb-0 text-warning">{{ $statistics->draft_posts }}</h2>
                                <p class="text-muted mb-0">Черновиков</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h2 class="mb-0 text-info">{{ number_format($statistics->total_views) }}</h2>
                                <p class="text-muted mb-0">Просмотров</p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="p-3">
                                <h3 class="mb-0">{{ $statistics->this_week_posts }}</h3>
                                <p class="text-muted mb-0"><i class="fas fa-calendar-week"></i> За эту неделю</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h3 class="mb-0">{{ $statistics->this_month_posts }}</h3>
                                <p class="text-muted mb-0"><i class="fas fa-calendar-alt"></i> За этот месяц</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h3 class="mb-0">{{ $statistics->total_comments }}</h3>
                                <p class="text-muted mb-0"><i class="fas fa-comments"></i> Комментариев</p>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-center text-muted py-5">Статистика недоступна</p>
                @endif
            </div>
        </div>

        <!-- Последние статьи -->
        <div class="card mt-4">
            <div class="card-header">
                <h4 class="mb-0">Последние статьи</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPosts as $post)
                                <tr>
                                    <td>
                                        <strong>{{ $post->post_title }}</strong>
                                    </td>
                                    <td>
                                        @php
                                            $statusLabel = match ($post->post_status) {
                                                'publish' => 'Опубликовано',
                                                'draft' => 'Черновик',
                                                'pending' => 'Ожидает проверки',
                                                'future' => 'Отложенная публикация',
                                                default => (string) $post->post_status,
                                            };
                                            $statusBadgeClass = match ($post->post_status) {
                                                'publish' => 'bg-success',
                                                'draft' => 'bg-warning',
                                                'pending' => 'bg-secondary',
                                                'future' => 'bg-info',
                                                default => 'bg-secondary',
                                            };
                                            $isPubliclyAccessible = filled($post->post_name)
                                                && $post->post_status === 'publish'
                                                && $post->post_date
                                                && $post->post_date->lte(now());
                                        @endphp
                                        <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td>{{ $post->post_date->format('d.m.Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('admin.posts.edit', $post->ID) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($isPubliclyAccessible)
                                            <a href="{{ route('post', $post->post_name) }}" target="_blank" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">У вас пока нет статей</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Боковая панель -->
    <div class="col-md-4">
        <!-- Профиль -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user"></i> Профиль</h5>
            </div>
            <div class="card-body text-center">
                <h4>{{ $user->display_name }}</h4>
                <p class="text-muted mb-2">{{ $user->user_email }}</p>
                @if($user->getRole())
                    <span class="badge bg-{{ $user->isSuperAdmin() ? 'danger' : ($user->isEditor() ? 'warning' : 'info') }} mb-2">
                        {{ $user->getRole()->display_name }}
                    </span>
                    <br>
                @endif
                @if($user->getPosition())
                    <small class="text-muted"><i class="fas fa-briefcase"></i> {{ $user->getPosition() }}</small>
                @endif
            </div>
        </div>

        <!-- Быстрые действия -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Быстрые действия</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="{{ route('admin.posts') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-newspaper"></i> Мои статьи
                </a>
                <a href="{{ route('admin.profile') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-user-edit"></i> Редактировать профиль
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

