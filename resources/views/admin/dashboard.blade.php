@extends('layouts.admin')
@section('title', 'Главная панель управления')
@section('content')
<div class="container-fluid">
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="mb-2"><i class="fas fa-tachometer-alt"></i> Панель управления</h1>
                            <p class="mb-0 opacity-75">Добро пожаловать в систему управления Нота Миру</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="mb-2">
                                <small class="opacity-75">Сегодня</small>
                                <div class="fs-4 fw-bold">{{ now()->format('d.m.Y') }}</div>
                            </div>
                            <div>
                                <small class="opacity-75">Время</small>
                                <div class="fs-5">{{ now()->format('H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Row -->
    <div class="row mb-4">
        <!-- Content Stats -->
        <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3" style="width: 60px; height: 60px; background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-newspaper text-white fa-2x"></i>
                    </div>
                    <h4 class="mb-1">{{ number_format($stats['posts']) }}</h4>
                    <p class="text-muted mb-2">Всего статей</p>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3" style="width: 60px; height: 60px; background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-eye text-white fa-2x"></i>
                    </div>
                    <h4 class="mb-1">{{ number_format($visitorStats['total_page_views']) }}</h4>
                    <p class="text-muted mb-2">Всего просмотров</p>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: 85%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3" style="width: 60px; height: 60px; background: linear-gradient(135deg, #36b9cc 0%, #258391 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-users text-white fa-2x"></i>
                    </div>
                    <h4 class="mb-1">{{ number_format($visitorStats['total_unique_visitors']) }}</h4>
                    <p class="text-muted mb-2">Уникальных посетителей</p>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-info" style="width: 75%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3" style="width: 60px; height: 60px; background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-chart-line text-white fa-2x"></i>
                    </div>
                    <h4 class="mb-1">{{ number_format($viewStatistics->sum('views')) }}</h4>
                    <p class="text-muted mb-2">Просмотров за 30 дней</p>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-warning" style="width: 90%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics Row -->
    <div class="row mb-4">
        <!-- Visitor Trends Chart -->
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area"></i> Статистика посещений за 30 дней
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="visitorsChart" width="100%" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Status -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt"></i> Быстрые действия
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Создать статью
                        </a>
                        <a href="{{ route('admin.posts') }}" class="btn btn-outline-primary">
                            <i class="fas fa-newspaper"></i> Управление статьями
                        </a>
                        <a href="{{ route('admin.content-quality') }}" class="btn btn-outline-success">
                            <i class="fas fa-chart-bar"></i> Анализ качества
                        </a>
                        <a href="{{ route('admin.seo-analysis') }}" class="btn btn-outline-info">
                            <i class="fas fa-chart-pie"></i> SEO Анализ
                        </a>
                        <div class="border-top my-3"></div>
                        <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-secondary">
                            <i class="fas fa-external-link-alt"></i> Просмотреть сайт
                        </a>
                    </div>

                    <!-- System Status -->
                    <div class="mt-4">
                        <h6 class="text-muted mb-3">Статус системы</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>База данных</span>
                            <span class="badge bg-success">Online</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Кеш</span>
                            <span class="badge bg-success">Работает</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Последнее обновление</span>
                            <span class="badge bg-info">{{ now()->format('H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Performance Row -->
    <div class="row mb-4">
        <!-- Top Performing Content -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-trophy"></i> Лучшие статьи недели
                    </h6>
                    <span class="badge bg-primary">{{ $topWeekPosts->count() }} статей</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <tbody>
                                @forelse($topWeekPosts->take(5) as $index => $item)
                                    <tr>
                                        <td width="40">
                                            <div class="text-center">
                                                @if($index == 0)
                                                    <i class="fas fa-crown text-warning fa-lg"></i>
                                                @elseif($index == 1)
                                                    <i class="fas fa-medal text-secondary fa-lg"></i>
                                                @elseif($index == 2)
                                                    <i class="fas fa-award text-warning fa-lg"></i>
                                                @else
                                                    <span class="badge bg-light text-dark">{{ $index + 1 }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <a href="{{ route('admin.posts.edit', $item->post_id) }}" class="text-decoration-none">
                                                        <strong class="text-dark">{{ Str::limit($item->post->post_title, 45) }}</strong>
                                                    </a>
                                                    <div class="text-muted small">
                                                        <i class="fas fa-user"></i> {{ $item->post->author->display_name ?? 'Неизвестен' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-primary fs-6">{{ number_format($item->view_count) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                                            <div>Нет данных за неделю</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history"></i> Последние действия
                    </h6>
                    <a href="{{ route('admin.activity-log') }}" class="btn btn-sm btn-outline-primary">Все</a>
                </div>
                <div class="card-body">
                    @php
                        $recentActivities = $recentActivities ?? collect();
                    @endphp

                    @if($recentActivities->count() > 0)
                        <div class="timeline">
                            @foreach($recentActivities as $activity)
                                <div class="timeline-item mb-3">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-dark">{{ $activity->getActionText() }}</div>
                                                <small class="text-muted">
                                                    <i class="fas fa-user"></i> {{ $activity->user->display_name ?? 'Система' }}
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-clock"></i> {{ $activity->created_at->diffForHumans() }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <div>Нет недавних действий</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- System Info & Quick Stats Row -->
    <div class="row">
        <!-- System Information -->
        <div class="col-xl-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-server"></i> Информация о системе
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center mb-3">
                                <div class="h5 mb-0">{{ number_format($stats['pages']) }}</div>
                                <small class="text-muted">Страниц</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center mb-3">
                                <div class="h5 mb-0">{{ number_format($stats['categories']) }}</div>
                                <small class="text-muted">Категорий</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center mb-3">
                                <div class="h5 mb-0">{{ number_format($stats['comments']) }}</div>
                                <small class="text-muted">Комментариев</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h5 mb-0">{{ app()->version() }}</div>
                                <small class="text-muted">Laravel</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Quality Quick Stats -->
        <div class="col-xl-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-star"></i> Качество контента
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $excellentCount = $seoQuality['excellent'] ?? 0;
                        $goodCount = $seoQuality['good'] ?? 0;
                        $needsWorkCount = $seoQuality['needs_work'] ?? 0;
                        $totalPosts = $seoQuality['total'] ?? 0;
                    @endphp

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-success">Отличное SEO</span>
                            <span class="badge bg-success">{{ $excellentCount }}</span>
                        </div>
                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: {{ $totalPosts > 0 ? ($excellentCount / $totalPosts * 100) : 0 }}%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-info">Хорошее SEO</span>
                            <span class="badge bg-info">{{ $goodCount }}</span>
                        </div>
                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: {{ $totalPosts > 0 ? ($goodCount / $totalPosts * 100) : 0 }}%"></div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-warning">Требует улучшения</span>
                            <span class="badge bg-warning">{{ $needsWorkCount }}</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: {{ $totalPosts > 0 ? ($needsWorkCount / $totalPosts * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="col-xl-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-link"></i> Полезные ссылки
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.sitemap') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-sitemap"></i> Управление Sitemap
                        </a>
                        <a href="{{ route('admin.categories') }}" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-folder"></i> Категории
                        </a>
                        <a href="{{ route('admin.menu') }}" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-bars"></i> Меню сайта
                        </a>
                        <a href="{{ route('admin.banners') }}" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-rectangle-ad"></i> Баннеры
                        </a>
                        <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-users"></i> Пользователи
                        </a>
                        <a href="{{ route('admin.author-statistics') }}" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-chart-line"></i> Статистика авторов
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for Analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare data for the visitors chart
@php
    $chartLabels = [];
    $chartViews = [];
    $chartVisitors = [];

    foreach ($viewStatistics as $stat) {
        $chartLabels[] = \Carbon\Carbon::parse($stat->date)->format('d.m');
        $chartViews[] = $stat->views;
    }

    foreach ($dailyStatistics as $visitor) {
        $chartVisitors[] = $visitor->unique_visitors ?? 0;
    }
@endphp

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('visitorsChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Просмотры страниц',
                data: @json($chartViews),
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Уникальные посетители',
                data: @json($chartVisitors),
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
});
</script>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.avatar {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 8px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 3px solid #fff;
    z-index: 1;
}

.timeline-content {
    background: #f8f9fa;
    padding: 12px 16px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.badge {
    font-weight: 500;
    border-radius: 6px;
}

.progress {
    border-radius: 3px;
}

.table-responsive {
    border-radius: 8px;
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }

    .h1, h1 {
        font-size: 1.5rem;
    }

    .fs-2 {
        font-size: 1.25rem !important;
    }
}
</style>
@endsection
