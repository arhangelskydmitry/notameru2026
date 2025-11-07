@extends('layouts.admin')
@section('title', 'Главная панель управления')
@section('content')
<div class="container-fluid">
    <h1 class="mb-4"><i class="fas fa-dashboard"></i> Панель управления</h1>
    
    <!-- Статистика контента -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <i class="fas fa-newspaper fa-3x mb-3"></i>
                    <h6 class="card-title">Всего статей</h6>
                    <p class="card-text fs-2">{{ number_format($stats['posts']) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <i class="fas fa-file-alt fa-3x mb-3"></i>
                    <h6 class="card-title">Страниц</h6>
                    <p class="card-text fs-2">{{ number_format($stats['pages']) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <i class="fas fa-folder fa-3x mb-3"></i>
                    <h6 class="card-title">Категорий</h6>
                    <p class="card-text fs-2">{{ number_format($stats['categories']) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-white">
                <div class="card-body">
                    <i class="fas fa-comments fa-3x mb-3"></i>
                    <h6 class="card-title">Комментариев</h6>
                    <p class="card-text fs-2">{{ number_format($stats['comments']) }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Статистика посетителей -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h6 class="card-title">Уникальных посетителей</h6>
                    <p class="card-text fs-2">{{ number_format($visitorStats['total_unique_visitors']) }}</p>
                    <small>Сегодня: {{ number_format($visitorStats['today_unique_visitors']) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-gradient" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <div class="card-body">
                    <i class="fas fa-eye fa-3x mb-3"></i>
                    <h6 class="card-title">Всего просмотров</h6>
                    <p class="card-text fs-2">{{ number_format($visitorStats['total_page_views']) }}</p>
                    <small>Сегодня: {{ number_format($visitorStats['today_page_views']) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-gradient" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <div class="card-body">
                    <i class="fas fa-chart-line fa-3x mb-3"></i>
                    <h6 class="card-title">Просмотров за 30 дней</h6>
                    <p class="card-text fs-2">{{ number_format($viewStatistics->sum('views')) }}</p>
                    <small>Средний: {{ number_format($viewStatistics->avg('views'), 1) }}/день</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-gradient" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                <div class="card-body">
                    <i class="fas fa-fire fa-3x mb-3"></i>
                    <h6 class="card-title">Топ статья недели</h6>
                    @if($topWeekPosts->isNotEmpty())
                        <p class="card-text fs-2">{{ number_format($topWeekPosts->first()->view_count) }}</p>
                        <small>{{ Str::limit($topWeekPosts->first()->post->post_title, 25) }}</small>
                    @else
                        <p class="card-text fs-2">0</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Рейтинги статей -->
    <div class="row mb-4">
        <!-- Топ за неделю -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Топ-10 статей за неделю</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Статья</th>
                                    <th style="width: 100px;" class="text-end">Просмотры</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topWeekPosts as $index => $item)
                                    <tr>
                                        <td>
                                            @if($index < 3)
                                                <span class="badge bg-warning text-dark">{{ $index + 1 }}</span>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.posts.edit', $item->post_id) }}" class="text-decoration-none">
                                                {{ Str::limit($item->post->post_title, 50) }}
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-primary">{{ number_format($item->view_count) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Нет данных</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Топ за год -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-crown"></i> Топ-10 статей за год</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Статья</th>
                                    <th style="width: 100px;" class="text-end">Просмотры</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topYearPosts as $index => $item)
                                    <tr>
                                        <td>
                                            @if($index < 3)
                                                <span class="badge bg-warning text-dark">{{ $index + 1 }}</span>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.posts.edit', $item->post_id) }}" class="text-decoration-none">
                                                {{ Str::limit($item->post->post_title, 50) }}
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-success">{{ number_format($item->view_count) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Нет данных</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Быстрые действия -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-bolt"></i> Быстрые действия</h5>
        </div>
        <div class="card-body">
            <a href="{{ route('admin.posts') }}" class="btn btn-primary me-2 mb-2">
                <i class="fas fa-newspaper"></i> Управление статьями
            </a>
            <a href="{{ route('admin.pages') }}" class="btn btn-success me-2 mb-2">
                <i class="fas fa-file-alt"></i> Управление страницами
            </a>
            <a href="{{ route('admin.categories') }}" class="btn btn-info me-2 mb-2">
                <i class="fas fa-folder"></i> Управление категориями
            </a>
            <a href="{{ route('admin.menu') }}" class="btn btn-warning me-2 mb-2">
                <i class="fas fa-bars"></i> Настройка меню
            </a>
            <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-secondary me-2 mb-2">
                <i class="fas fa-external-link-alt"></i> Открыть сайт
            </a>
        </div>
    </div>
    
    <!-- Информация -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Информация</h5>
        </div>
        <div class="card-body">
            <p><strong>Адрес сайта:</strong> <a href="{{ route('home') }}" target="_blank">{{ config('app.url') }}</a></p>
            <p><strong>Кастомная админка:</strong> {{ route('admin.dashboard') }}</p>
            <p class="mb-0"><strong>Версия Laravel:</strong> {{ app()->version() }}</p>
        </div>
    </div>
</div>
@endsection

