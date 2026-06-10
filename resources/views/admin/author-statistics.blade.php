@extends('layouts.admin')
@section('title', 'Статистика авторов')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-chart-line"></i> Статистика авторов</h2>
</div>

<div class="row">
    @forelse($authors as $author)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-{{ $author->isSuperAdmin() ? 'danger' : ($author->isEditor() ? 'warning' : 'info') }} text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user"></i> {{ $author->display_name }}
                    </h5>
                    <small>{{ $author->getRole()->display_name }} • {{ $author->getPosition() ?? 'Без должности' }}</small>
                </div>
                <div class="card-body">
                    @if($author->statistics)
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <h3 class="mb-0">{{ $author->statistics->total_posts }}</h3>
                                <small class="text-muted">Всего статей</small>
                            </div>
                            <div class="col-6">
                                <h3 class="mb-0 text-success">{{ $author->statistics->published_posts }}</h3>
                                <small class="text-muted">Опубликовано</small>
                            </div>
                        </div>

                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <h4 class="mb-0 text-info">{{ number_format($author->statistics->total_views) }}</h4>
                                <small class="text-muted">Просмотров</small>
                            </div>
                            <div class="col-6">
                                <h4 class="mb-0 text-primary">{{ $author->statistics->total_comments }}</h4>
                                <small class="text-muted">Комментариев</small>
                            </div>
                        </div>

                        <hr>

                        <div class="row text-center">
                            <div class="col-6">
                                <p class="mb-0"><strong>{{ $author->statistics->this_month_posts }}</strong></p>
                                <small class="text-muted">За этот месяц</small>
                            </div>
                            <div class="col-6">
                                <p class="mb-0"><strong>{{ $author->statistics->this_week_posts }}</strong></p>
                                <small class="text-muted">За эту неделю</small>
                            </div>
                        </div>

                        @if($author->statistics->last_post_date)
                            <hr>
                            <p class="text-center mb-0">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> Последняя статья: 
                                    {{ $author->statistics->last_post_date->format('d.m.Y') }}
                                </small>
                            </p>
                        @endif
                    @else
                        <p class="text-center text-muted">Статистика недоступна</p>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.users.edit', $author->ID) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Редактировать
                        </a>
                        <a href="{{ route('admin.posts') }}?author={{ $author->ID }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-newspaper"></i> Статьи
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <h4>Авторов пока нет</h4>
                    <p class="text-muted">Добавьте пользователей и назначьте им роли</p>
                </div>
            </div>
        </div>
    @endforelse
</div>

@endsection

